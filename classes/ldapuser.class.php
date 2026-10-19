<?php

namespace SieEsxi;

/**
 * LDAP person entry. Attribute values are exposed through __get().
 *
 * @property string $thumbnailphoto
 * @property string $ismanager
 * @property array<int|string, mixed> $directreports
 * @property string $samaccountname
 * @property string $ipphone
 * @property string $userprincipalname
 * @property array<int|string, mixed> $memberof
 */
class LdapUser
{
    private \LDAP\Connection|false $connection = false;

    /** @var array<string, mixed> */
    private array $data = array();

    /**
     * @param array{0: \LDAP\Connection|false, 1: string} $params
     */
    public function __construct(array $params)
    {
        $this->data = array();
        $connection = $params[0];
        $this->connection = $connection instanceof \LDAP\Connection ? $connection : false;
        $this->getuser($params[1]);
    }

    public function __get(string $property): mixed
    {
        if (!array_key_exists($property, $this->data)) {
            return false;
        }

        $value = $this->data[$property];
        if (!is_array($value)) {
            return false;
        }

        $count = $value['count'] ?? 0;
        if (is_numeric($count) && (int) $count > 1) {
            return $value;
        }

        return $value[0] ?? false;
    }

    public function getimage(): bool
    {
        $photo = $this->thumbnailphoto;
        if (!$photo) {
            return false;
        }

        $img = imagecreatefromstring($photo);
        $account = $this->data['samaccountname'][0] ?? '';
        $account = is_string($account) ? $account : '';
        if (is_object($img)) {
            imagejpeg($img, 'images/users/' . $account . '.jpg');
        }

        return true;
    }

    public function getemployees(): \LdapGroup
    {
        if ($this->ismanager == 'TRUE') {
            $ldapgroup = new \LdapGroup(array($this->connection, false));
            $reports = $this->directreports;
            $reported = $reports['count'] ?? 0;
            $count = is_numeric($reported) ? (int) $reported : 0;
            for ($k = 0; $k < $count; $k++) {
                $report = $reports[$k] ?? '';
                $ldapgroup->add(new LdapUser(array(
                    $this->connection,
                    is_string($report) ? $report : '',
                )));
            }

            return $ldapgroup;
        }

        return new \LdapGroup(array(false, false));
    }

    public function dump(): void
    {
        print '<pre>';
        print_r($this->data);
        print '</pre>';
    }

    private function getuser(string $user): void
    {
        if (!$this->connection instanceof \LDAP\Connection) {
            return;
        }

        $attributes = array('*');
        if (stristr($user, 'DC=corp,DC=intel,DC=com') === false) {
            $filter = '(&(objectClass=user)(objectCategory=person)(sAMAccountName=' . $user . '))';
            $result = ldap_search(
                $this->connection,
                'DC=corp,DC=intel,DC=com',
                $filter,
                $attributes
            );
        } else {
            $filter = '(&(objectClass=user)(objectCategory=person))';
            $result = ldap_read($this->connection, $user, $filter, $attributes);
        }

        if ($result instanceof \LDAP\Result) {
            $this->storeUserEntry($result);
        }
    }

    private function storeUserEntry(\LDAP\Result $result): void
    {
        if (!$this->connection instanceof \LDAP\Connection) {
            return;
        }

        $userProperties = ldap_get_entries($this->connection, $result);
        if (is_array($userProperties) && ($userProperties['count'] ?? 0) > 0) {
            $entry = $userProperties[0];
            if (is_array($entry)) {
                $this->data = $entry;
            }
        }
    }

    public function __toString(): string
    {
        $name = $this->data['samaccountname'][0] ?? '';

        return is_string($name) ? $name : '';
    }
}
