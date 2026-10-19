<?php

function commandOutput(string $command): ?string
{
    $pipes = array();
    $process = proc_open(
        $command,
        array(
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w'),
        ),
        $pipes
    );

    if (!is_resource($process)) {
        return null;
    }

    fclose($pipes[0]);
    $read = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    proc_close($process);

    if (!is_string($read) || $read === '') {
        return null;
    }

    return $read;
}

/**
 * @return list<string>
 */
function diskPaths(): array
{
    $disks = array();
    # $disks[0] = "/nfs/sie/disks/SIE-EC_esxi_disk003/";
    # $disks[1] = "/nfs/sie/disks/SIE-EC_esxi_disk004/";

    return $disks;
}

function ddisks(): void
{
    global $nfsdiskarea, $nfsdiskfree;

    foreach (diskPaths() as $disk) {
        $du = commandOutput('du -h ' . $disk);
        $df = commandOutput('df -h ' . $disk . '  | tail -n 1');
        $parts = preg_split('/\s+/', trim($df ?? ''));
        if (is_array($parts)) {
            $nfsdiskfree[] = $parts;
        }

        // array_merge does not update its arguments; the historical call discarded the result.
        $merged = array_merge($nfsdiskarea, explode("\n", $du ?? ''));
        unset($merged);
    }
}

function memorySize(int|float|string $memory): string
{
    $numeric = is_numeric($memory) ? (float) $memory : 0.0;
    if ($numeric > 1023) {
        return (string) round($numeric / 1024.0, 2) . 'gb';
    }

    return (string) $memory . 'mb';
}

function readTextFile(string $path): string
{
    $contents = file_get_contents($path);

    return is_string($contents) ? $contents : '';
}

function vmSidecarPath(array $vmfile, string $suffix): string
{
    $part = static function (array $parts, int $index): string {
        $value = $parts[$index] ?? '';

        return is_string($value) ? $value : '';
    };

    return $part($vmfile, 0) . '.' . $part($vmfile, 1) . '.' . $part($vmfile, 2) . '.'
        . $part($vmfile, 3) . '.' . $part($vmfile, 4) . '.' . $suffix;
}

/**
 * @return array{0: string, 1: string, 2: string, 3: int}
 */
function readVmBundle(array $vmfile, bool $trackMissing): array
{
    $info = '';
    $device = '';
    $power = '';
    $missing = 0;

    foreach (array('info', 'device', 'power') as $suffix) {
        list($found, $contents) = loadVmSidecar($vmfile, $suffix);
        if ($found && is_string($contents)) {
            if ($suffix === 'info') {
                $info = $contents;
            } elseif ($suffix === 'device') {
                $device = $contents;
            } else {
                $power = $contents;
            }
        } elseif (!$found && $trackMissing) {
            $missing = 1;
        }
    }

    return array($info, $device, $power, $missing);
}

/**
 * @return array{0: bool, 1: string|false}
 */
function loadVmSidecar(array $vmfile, string $suffix): array
{
    $path = vmSidecarPath($vmfile, $suffix);
    if (!file_exists($path)) {
        return array(false, false);
    }

    $contents = file_get_contents($path);

    return array(true, is_string($contents) ? $contents : false);
}

function capture(string $pattern, string $subject): array
{
    if ($pattern === '') {
        return array();
    }

    $match = array();
    preg_match($pattern, $subject, $match);

    return $match;
}

function matchText(array $match, int $index): string
{
    $value = $match[$index] ?? '';

    return is_string($value) ? $value : '';
}

/**
 * @return array{
 *   status: array<int, string>,
 *   name: array<int, string>,
 *   memory: array<int, string>,
 *   mac: array<int, string>,
 *   cpu: array<int, string>,
 *   customer: array<int, string>,
 *   powerinfo: array<int, string>,
 *   vmpath: array<int, string>,
 *   vtools: array<int, string>
 * }
 */
function parseVmFields(string $info, string $device, string $power): array
{
    return array(
        'status' => capture('/overallStatus = "(.*?)"/', $info),
        'name' => capture('/name = "(.*?)",/', $info),
        'memory' => capture('/memorySizeMB = ([0-9]+),/', $info),
        'mac' => capture('/macAddress = "(.*?)",/', $device),
        'cpu' => capture('/numCpu = ([0-9]+),/', $info),
        'customer' => capture('/annotation = "(.*?)",/sm', $info),
        'powerinfo' => capture('/Powered ([a-zA-Z]+)/', $power),
        'vmpath' => capture('/vmPathName = "\[(.*?)\] (.*?)",/', $info),
        'vtools' => capture('/toolsStatus = "(.*?)",/', $info),
    );
}

/**
 * @param array<int, string> $customer
 * @return array{0: string, 1: string, 2: string, 3: string, 4: string, 5: string}
 */
function parseCustomerAnnotation(array $customer): array
{
    $custproj = '';
    $custos = '';
    $custver = '';
    $custarch = '';
    $custcon = '';
    $cust = '';
    $annotation = matchText($customer, 1);

    if (stristr($annotation, ':') !== false) {
        foreach (explode("\n", $annotation) as $detail) {
            $det = explode(':', $detail);
            switch (strtolower(trim($det[0]))) {
                case 'project':
                    $custproj = trim($det[1] ?? '');
                    break;
                case 'email':
                    $custcon = trim($det[1] ?? '');
                    break;
                case 'customer':
                    $cust = trim($det[1] ?? '');
                    break;
                case 'os':
                    $custos = trim($det[1] ?? '');
                    break;
                case 'version':
                    $custver = trim($det[1] ?? '');
                    break;
                case 'arch':
                    $custarch = trim($det[1] ?? '');
                    break;
            }
        }
    }

    return array($custproj, $custos, $custver, $custarch, $custcon, $cust);
}

function inventoryVmCount(): int
{
    global $numvms;

    return is_int($numvms) ? $numvms : 0;
}

function hostNodeRegistry(): array
{
    global $hnodes;

    return is_array($hnodes) ? $hnodes : array();
}

function quickList(?string $filter = null): void
{
    global $ldap, $counter, $libdir;

    ddisks();
    inventoryVmCount();

    $nodes = getNodes($filter);
    $html = '';
    $html .= "<table width='1200'>";
    $vmfile = array();

    foreach ($nodes as $node) {
        $vms = getNodeVms($node);

        $html .= '<tr>';
        if ($counter == 0) {
            $html .= "<td colspan='14'>";
            $counter++;
        } else {
            $html .= "<td colspan='14'><br>";
        }
        $html .= "<div id='" . base64_encode($node) . "'><table><tr><td width='60'>";
        $html .= "<img src='content/images/vm.png' border='0' align='middle' /></td>";
        $html .= "<td><a name='" . base64_encode($node) . "'><h2>" . $node . "</h2></a></td></tr>";
        $html .= '</table></div></td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= "<td colspan='3'>&nbsp;</td>";
        $html .= "<td colspan='2'><b>Hardware</b></td>";
        $html .= '<td><b>Memory</b></td>';
        $html .= "<td colspan='2'><b>OS</b></td>";
        $html .= '<td><b>IpAddress</b></td>';
        $html .= "<td colspan='3'><b>Mac</b></td>";
        $html .= '<td><b>Serial</b></td>';
        $html .= '<td>&nbsp;</td>';
        $html .= '</tr>';

        $version = readTextFile($libdir . '/content/downloaded/' . $node . '.version');
        $hostinfo = getHostHardwareInfo($node);
        $netinfo = getHostNetworkInfo($node);
        $mem = $hostinfo[0];
        $vendor = $hostinfo[1];
        $model = $hostinfo[2];
        $serial = $hostinfo[3];
        $mac = $netinfo[1];
        $ipaddr = $netinfo[2];

        $html .= '<tr>';
        $html .= "<td colspan='2'>&nbsp;</td>";
        $html .= "<td><img src='content/images/host.png' /></td>";
        $html .= "<td colspan='2'>" . $vendor . ' ' . $model . '</td>';
        $html .= '<td>' . (string) $mem . '</td>';
        $html .= "<td colspan='2'>" . $version . '</td>';
        $html .= '<td>' . $ipaddr . '</td>';
        $html .= "<td colspan='3'>" . $mac . '</td>';
        $html .= '<td>' . $serial . '</td>';
        $html .= '<td></td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= "<td colspan='14'><br></td>";
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= "<td colspan='3'><br></td>";
        $html .= "<td colspan='11' style='border-top:1px dashed #99f'><br></td>";
        $html .= '</tr>';

        $hostName = $node;

        $html .= '<tr>';
        $html .= "<td width='15'>&nbsp;</td>";
        $html .= "<td width='20'>&nbsp;</td>";
        $html .= "<td width='20'>&nbsp;</td>";
        $html .= '<td><b>Name</b></td>';
        $html .= '<td><b>Mac</b></td>';
        $html .= '<td><b>Memory</b></td>';
        $html .= '<td><b>CPUS</b></td>';
        $html .= '<td><b>Project</b></td>';
        $html .= '<td><b>OS</b></td>';
        $html .= '<td><b>Version</b></td>';
        $html .= '<td><b>Arch</b></td>';
        $html .= '<td><b>Customer</b></td>';
        $html .= '<td><b>Contact</b></td>';
        $html .= '<td><b>Tools</b></td>';
        $html .= '</tr>';

        foreach ($vms as $vm) {
            $vmfile = explode('.', $vm);
            list($info, $device, $power, $missingfile) = readVmBundle($vmfile, true);

            if ($missingfile == 1) {
                continue;
            }

            $vmid = $vmfile[4] ?? '';
            $fields = parseVmFields($info, $device, $power);
            $name = $fields['name'];
            $memory = $fields['memory'];
            $vmMac = $fields['mac'];
            $cpu = $fields['cpu'];
            $customer = $fields['customer'];
            $powerinfo = $fields['powerinfo'];
            $vmpathMatch = $fields['vmpath'];
            $vtools = $fields['vtools'];

            $vmMac[1] = matchText($vmMac, 1);
            $pwrinf = matchText($powerinfo, 1);
            if ($pwrinf === '') {
                $pwrinf = 'off';
            }

            list($custproj, $custos, $custver, $custarch, $custcon, $cust) = parseCustomerAnnotation($customer);

            $ldapuser = $ldap instanceof Ldap ? $ldap->getldapuser($cust) : false;
            if (!$ldapuser instanceof \SieEsxi\LdapUser) {
                throw new \Error('Attempt to read property "ipphone" on false');
            }
            $phone = $ldapuser->ipphone;

            $encodedPath = base64_encode(matchText($vmpathMatch, 1) . '/' . matchText($vmpathMatch, 2));
            $vmhost = base64_encode($hostName);
            $console = 'index_console.php?host=' . $vmhost . '&vmpath=' . $encodedPath . '&vmid=' . $vmid;
            $html .= '<tr>';
            $html .= '<td></td>';
            $html .= "<td><img src='content/images/vm" . $pwrinf . ".png' border='0' width='16' /></td>";
            $html .= "<td><a title='Console' class='various' data-fancybox-type='iframe' href='" . $console . "'>";
            $html .= "<img src='content/images/vmconsole.png'/></a></td>";
            $vmName = matchText($name, 1);
            $html .= "<td><a title='Console' class='various' data-fancybox-type='iframe' href='"
                . $console . "'>" . $vmName . '</a></td>';
            $html .= '<td>' . $vmMac[1] . '</td>';
            $html .= '<td>' . memorySize(matchText($memory, 1)) . '</td>';
            $html .= '<td>' . matchText($cpu, 1) . '</td>';
            $html .= '<td>' . $custproj . '</td>';
            $html .= '<td>' . $custos . '</td>';
            $html .= '<td>' . $custver . '</td>';
            $html .= '<td>' . $custarch . '</td>';
            $html .= "<td><a href='im:&lt;sip:" . $custcon . "&gt;' title='" . $custcon . "'>" . $custcon . '</a></td>';
            $html .= "<td><a href='mailto:" . $custcon . "' style='margin-right:3px'>";
            $html .= "<img src='content/images/outlook.png' border='0' width='16'/></a>";
            $html .= "<a href='im:&lt;sip:" . $custcon . "&gt;' style='margin-right:3px'>";
            $html .= "<img src='content/images/lync.png' border='0' width='16'/></a>";
            $html .= "<a href='tel:" . $phone . "' title='" . $phone . "'  style='margin-right:3px'>";
            $html .= "<img src='content/images/telephone.png' border='0' width='16'/></a></td>";
            $tool = matchText($vtools, 1);
            $html .= "<td><img src='content/images/" . $tool . ".png' title='" . $tool
                . "' alt='" . $tool . "' width='16'/></td>";
            $html .= '</tr>';
        }

        if (count($vms) == 0) {
            $html .= '<tr>';
            $html .= "<td colspan='14' align='center'><b style='color:red;'>0 virtual machines</b></td>";
            $html .= '</tr>';
        }

        $html .= '<tr>';
        $html .= "<td colspan='14' style='border-bottom:1px dashed #99f'><br><center>";
        $html .= "<span style='font-size:7pt'>Last polled : <b>";
        $infoPath = vmSidecarPath($vmfile, 'info');
        if (file_exists($infoPath)) {
            $mtime = filemtime($infoPath);
            if (is_int($mtime)) {
                $html .= date('l jS \of F Y h:i:s A', $mtime);
            }
        }
        $html .= '</b></style></center><br><br></td>';
        $html .= '</tr>';
    }

    $html .= '</table>';

    print $html;
}

function readHardware(string $host): void
{
    global $libdir;

    $hwinfo = readTextFile($libdir . '/content/downloaded/' . $host . '.hwinfo');
    $netinfo = readTextFile($libdir . '/content/downloaded/' . $host . '.netinfo');

    $dns = capture('/address.*?\[(.*?)\]/sm', $netinfo);
    $ipaddr = array();
    preg_match_all('/ipAddress = "(.*?)"/', $netinfo, $ipaddr);
    $memory = capture('/memorySize = ([0-9]+),/', $hwinfo);
    $cpunum = capture('/numCpuPackages = ([0-9]+)/', $hwinfo);
    $cpu = capture('/description = "(.*?)"/', $hwinfo);
    $cpunumt = capture('/numCpuThreads = ([0-9]+)/', $hwinfo);
    $addresses = $ipaddr[1];

    $html = '';
    $html .= "<table width='500px' cellpadding='5'>";
    $html .= '<tr>';
    $html .= "<td width='25%'><b>DNS</b></td><td width='75%'>" . matchText($dns, 1) . '</td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= "<td width='25%'><b>IP</b></td><td width='75%'>" . implode(' ', $addresses) . '</td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $memoryLabel = memorySize((float) matchText($memory, 1) / 1024.0 / 1024.0);
    $html .= "<td width='25%'><b>Memory</b></td><td width='75%'>" . $memoryLabel . '</td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= "<td width='25%'><b>Name</b></td><td width='75%'>" . $host . '</td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $processors = matchText($cpunum, 1) . ' (' . matchText($cpunumt, 1) . ')';
    $html .= "<td width='25%'><b>Processors</b></td><td width='75%'>" . $processors . '</td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= "<td width='25%'><b>Processor type</b></td><td width='75%'>" . matchText($cpu, 1) . '</td>';
    $html .= '</tr>';
    $html .= '</table>';
    $html .= '';

    print $html;
}

function readBios(string $host): void
{
    global $libdir;

    $hwinfo = readTextFile($libdir . '/content/downloaded/' . $host . '.hwinfo');
    readTextFile($libdir . '/content/downloaded/' . $host . '.netinfo');

    $assettag = array();
    preg_match_all('/identifierValue = "(.*?)"/', $hwinfo, $assettag);
    $biosdate = capture('/releaseDate = "(.*?)"/', $hwinfo);
    $biosversion = capture('/biosVersion = "(.*?)"/', $hwinfo);
    $vendor = capture('/systemInfo.*?vendor = "(.*?)"/sm', $hwinfo);
    $model = capture('/systemInfo.*?model = "(.*?)"/sm', $hwinfo);
    $tags = $assettag[1];
    $asset = $tags[1] ?? '';

    $html = "<table width='500px' cellpadding='5'>";
    $html .= '<tr>';
    $html .= "<td width='25%'><b>Asset Tag</b></td><td width='75%'>" . $asset . '</td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= "<td width='25%'><b>Date</b></td><td width='75%'>" . matchText($biosdate, 1) . '</td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= "<td width='25%'><b>Version</b></td><td width='75%'>" . matchText($biosversion, 1) . '</td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= "<td width='25%'><b>Manufactuer</b></td><td width='75%'>" . matchText($vendor, 1) . '</td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= "<td width='25%'><b>Model</b></td><td width='75%'>" . matchText($model, 1) . '</td>';
    $html .= '</tr>';
    $html .= '</table>';

    print $html;
}

function cleanupstaleandlogs(): void
{
    global $libdir;

    commandOutput('rm -rvf ' . $libdir . '/content/temp/*');
}

/**
 * @return list<string>
 */
function getNodes(?string $filter = null): array
{
    hostNodeRegistry();

    $retNodes = array();
    $raw = commandOutput('/bin/ucat nodes key speciality');
    $nodes = explode("\n", $raw ?? '');

    foreach ($nodes as $node) {
        if (trim($node) == '') {
            continue;
        }

        $cNode = explode(' ', $node);
        $speciality = $cNode[1] ?? '';
        if ($filter == null) {
            if (strstr($speciality, 'ESX') !== false) {
                $retNodes[] = trim($cNode[0]);
            }
        } elseif ($speciality == $filter) {
            $retNodes[] = trim($cNode[0]);
        }
    }

    sort($retNodes);

    return $retNodes;
}

/**
 * @return list<string>
 */
function matchNodeFiles(string $pattern, bool $sortResults): array
{
    global $files;

    $matched = array();
    if ($pattern === '') {
        return $matched;
    }

    $known = is_array($files) ? $files : array();
    foreach ($known as $file) {
        if (!is_string($file)) {
            continue;
        }
        if (preg_match($pattern, $file) === 1) {
            $matched[] = $file;
        }
    }

    if ($sortResults) {
        sort($matched);
    }

    return $matched;
}

/**
 * @return list<string>
 */
function getNodeVms(string $node): array
{
    return matchNodeFiles('/' . $node . '.*/', true);
}

function getNodeVmsCount(string $node): int
{
    return count(getNodeVms($node));
}

/**
 * @return list<string>
 */
function getNodeVmById(string $node, string $vmid): array
{
    return matchNodeFiles('/' . $node . '.*?' . $vmid . '.*/', false);
}

function editform(): void
{
    global $ldap, $host, $vmid;

    $node = is_string($host) ? $host : '';
    $id = is_string($vmid) ? $vmid : '';
    $vm = getNodeVmById($node, $id);
    $vmfile = explode('.', $vm[0] ?? '');
    list($info, $device, $power) = readVmBundle($vmfile, false);

    $fields = parseVmFields($info, $device, $power);
    $name = matchText($fields['name'], 1);
    list($custproj, $custos, $custver, $custarch, $custcon, $cust) = parseCustomerAnnotation($fields['customer']);

    if ($ldap instanceof Ldap) {
        $ldap->getldapuser($cust);
    }

    $hostLabel = is_string($host) ? $host : '';
    print "\n        <form>\n        <table cellpadding='5'>\n        <tr>\n"
        . "                <td valign='top' align='right' width='120'>\n"
        . "                        <img src='content/images/vm.png'/>\n"
        . "                </td>\n"
        . "                <td colspan='2'><h1 style='margin-top:0px;padding-top:0px;"
        . "margin-bottom:0px;padding-bottom:0px;line-height:14pt'>" . $hostLabel . "</h1>\n"
        . "                                <h3 style='margin-top:5px;padding-top:0px;"
        . "line-height:14pt'><img src='content/images/vmoff.png''/> " . $name . " </h3></td>\n"
        . "        </tr>\n        <tr>\n"
        . "                <td align='right'>Project</td>\n"
        . "                <td><input type='text' id='project' name='project' value='"
        . $custproj . "' style='width:300px'></td>\n"
        . "        </tr>\n        <tr>\n"
        . "                <td align='right'>OS</td>\n"
        . "                <td><input type='text' id='os' name='os' value='"
        . $custos . "' style='width:300px'></td>\n"
        . "        </tr>\n        <tr>\n"
        . "                <td align='right'>Version</td>\n"
        . "                <td><input type='text' id='version' name='version' value='"
        . $custver . "'></td>\n"
        . "        </tr>\n        <tr>\n"
        . "                <td align='right'>Architecture</td>\n"
        . "                <td><input type='text' id='architecture' name='architecture' value='"
        . $custarch . "'></td>\n"
        . "        </tr>\n        <tr>\n"
        . "                <td align='right'>Customer Idsid</td>\n"
        . "                <td><input type='text' id='customer' name='customer' value='"
        . $cust . "'> &nbsp;<a href='javascript:checkname()'>"
        . "<img src='content/images/checknames.gif' border='0'></a></td>\n"
        . "        </tr>\n        <tr>\n"
        . "                <td align='right'>Customer Email</td>\n"
        . "                <td><input type='text' id='customercon' name='customercon' value='"
        . $custcon . "' style='width:300px' disabled='disabled'></td>\n"
        . "        </tr>\n        <tr>\n"
        . "                <td></td>\n"
        . "                <td><input type='button' value='Modify' onclick='validate()'></td>\n"
        . "        </tr>\n        </table>\n        </form>";
}

function writeDownloadFile(string $path, string $contents): void
{
    $handle = fopen($path, 'w');
    if (!is_resource($handle)) {
        return;
    }

    fwrite($handle, $contents);
    fclose($handle);
}

function stringResult(mixed $value): string
{
    return is_string($value) ? $value : '';
}

function sync(): void
{
    global $libdir;

    $vmware = new Vmware('root');
    $downloadtempdir = $libdir . '/content/temp';
    $downloaddir = $libdir . '/content/downloaded';
    $nodes = getNodes();

    cleanupstaleandlogs();

    foreach ($nodes as $node) {
        print $node . "\n";
        $vmnums = explode("\n", stringResult($vmware->gethostvms($node)));

        writeDownloadFile(
            $downloadtempdir . '/' . $node . '.hwinfo',
            stringResult($vmware->gethosthardware($node))
        );
        writeDownloadFile(
            $downloadtempdir . '/' . $node . '.netinfo',
            stringResult($vmware->gethostnetwork($node))
        );
        writeDownloadFile(
            $downloadtempdir . '/' . $node . '.version',
            stringResult($vmware->getversion($node))
        );

        foreach ($vmnums as $vmnum) {
            if (preg_match('/[0-9]{1,3}/', $vmnum) === 1) {
                writeDownloadFile(
                    $downloadtempdir . '/' . $node . '.' . $vmnum . '.info',
                    stringResult($vmware->getvmsummary($node, $vmnum))
                );
                writeDownloadFile(
                    $downloadtempdir . '/' . $node . '.' . $vmnum . '.device',
                    stringResult($vmware->getvmdevices($node, $vmnum))
                );
                writeDownloadFile(
                    $downloadtempdir . '/' . $node . '.' . $vmnum . '.power',
                    stringResult($vmware->getvmstate($node, $vmnum))
                );
            }
        }
    }

    commandOutput('rm -rvf ' . $downloaddir . '/*');
    commandOutput('mv ' . $downloadtempdir . '/* ' . $downloaddir . '/');
    commandOutput('chmod 770 ' . $downloaddir . '/*');
    commandOutput('chown ec_webadm:webadm  ' . $downloaddir . '/*');
}

/**
 * @return array{0: string, 1: string, 2: string}
 */
function getHostNetworkInfo(string $host): array
{
    $file = readTextFile('content/downloaded/' . $host . '.netinfo');
    $portgroup = capture('/\(vim\.host\.VirtualNic\.Config\) .*?portgroup = "(.*?)"/sm', $file);
    $mac = capture('/\(vim\.host\.VirtualNic\.Config\) .*?mac = "(.*?)"/sm', $file);
    $ipaddress = capture('/\(vim\.host\.VirtualNic\.Config\) .*?ipAddress = "(.*?)"/sm', $file);

    return array(matchText($portgroup, 1), matchText($mac, 1), matchText($ipaddress, 1));
}

/**
 * @return array{0: float, 1: string, 2: string, 3: string, 4: string, 5: string}
 */
function getHostHardwareInfo(string $host): array
{
    $file = readTextFile('content/downloaded/' . $host . '.hwinfo');
    $vendor = capture('/\(vim\.host\.HardwareInfo\) \{.*?vendor = "(.*?)",/sm', $file);
    $model = capture('/\(vim\.host\.HardwareInfo\) \{.*?model = "(.*?)",/sm', $file);
    $serial = capture('/identifierValue = "([a-zA-Z0-9]+)",/sm', $file);
    $packages = capture('/cpuInfo = \(vim\.host\.CpuInfo\) \{.*?numCpuPackages = ([0-9]+),/sm', $file);
    $cores = capture('/cpuInfo = \(vim\.host\.CpuInfo\) \{.*?numCpuCores = ([0-9]+),/sm', $file);
    $memorysize = capture('/memorySize = ([0-9]+),/', $file);
    $bytes = matchText($memorysize, 1);
    $gigabytes = $bytes === '' ? 0.0 : round((float) $bytes / 1024.0 / 1024.0 / 1024.0);

    return array(
        $gigabytes,
        matchText($vendor, 1),
        matchText($model, 1),
        matchText($serial, 1),
        matchText($packages, 1),
        matchText($cores, 1),
    );
}
