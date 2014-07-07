#!/usr/bin/perl -w
#
# Copyright 2006 VMware, Inc.  All rights reserved.
#
# This script schedules a task to snapshot a VM.  Only works with VirtualCenter

use strict;
use warnings;

use VMware::VIRuntime;

my %opts = (
   vm_name => {
      type => "=s",
      help => "VM name",
      required => 1,
   },
);

# read/validate options and connect to the server
Opts::add_options(%opts);
Opts::parse();
Opts::validate();
Util::connect();

my $vm_name = Opts::get_option('vm_name');

my $vm = Vim::find_entity_view(view_type => 'VirtualMachine',
                               filter =>{'name' => $vm_name });
if (!$vm) {
   die "VM '" . $vm_name . "' not found\n";   
}

# arguments
my $name = MethodActionArgument->new(
               value => PrimType->new('Sample snapshot task', 'string'));
my $description = MethodActionArgument->new(
               value => PrimType->new('Created from a sample script', 'string'));
my $memory = MethodActionArgument->new(
               value => PrimType->new(0, 'boolean'));
my $quiesce = MethodActionArgument->new(
               value => PrimType->new(0, 'boolean'));

# action object
my $snapshot_action = MethodAction->new(name => "CreateSnapshot", argument => [ $name, $description, $memory, $quiesce ] );

# schedule
my $scheduler = OnceTaskScheduler->new(runAt => '2010-01-01T10:00:00');

# spec
my $scheduled_task_spec =
   ScheduledTaskSpec->new(action => $snapshot_action,
                          description => 'Sample scheduled task',
                          enabled => 0,
                          name => 'Sample snapshot scheduled task',
                          scheduler => $scheduler);

# ScheduledTaskManager view
my $scheduled_task_mgr = Vim::get_view(
   mo_ref => Vim::get_service_content()->scheduledTaskManager);

# creating scheduled task
$scheduled_task_mgr->CreateScheduledTask(entity => $vm, spec => $scheduled_task_spec);

printf "Successfully created snapshot scheduled task\n";

# disconnect from the server
Util::disconnect();

