#!/usr/bin/perl -w
#
# Copyright 2006 VMware, Inc.  All rights reserved.
#
# This script snapshots all powered on VM's

use strict;
use warnings;

use VMware::VIRuntime;

Opts::parse();
Opts::validate();

Util::connect();

# get VirtualMachine views for all powered on VM's
my $vm_views = Vim::find_entity_views(view_type => 'VirtualMachine',
                                      filter => { 'runtime.powerState' => 'poweredOn' });

# snapshot each VM
foreach (@$vm_views) {
   $_->CreateSnapshot(name => 'snapshot sample',
                      description => 'Snapshot created from workshop sample',
                      memory => 0,
                      quiesce => 0);
   print "Snapshot complete for VM: " . $_->name . "\n";
}

Util::disconnect();
