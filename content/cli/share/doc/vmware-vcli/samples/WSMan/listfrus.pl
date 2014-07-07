#!/usr/bin/perl

use strict;
use warnings;

use VMware::VILib;
use WSMan::StubOps;

=pod
  This sample is used to get the list of all the FRUs on a system.
  
  USAGE:: perl listfrus.pl --server xyz.abc.com --username abc --password xxxx
=cut

$Util::script_version = "1.0";

my %opts = (
   namespace  => {
      type     => "=s",
      help     => "Namespace for all queries. Default is :root/cimv2",
      required => 0,
      default => "root/cimv2",
   },
   timeout  => {
      type  => "=s",
      help  => "Default http timeout for all the queries. Default is 120",
      required => 0,
      default => "120"
   }
   
);

Opts::set_option('protocol', 'http');
Opts::set_option('servicepath','/wsman');
Opts::set_option('portnumber', '80');
Opts::add_options(%opts);
Opts::parse();
Opts::validate();

doOperation();

sub doOperation {

         my %args = (
             path => Opts::get_option ('servicepath'),
              username => Opts::get_option ('username'),
              password => Opts::get_option ('password'),
              port => Opts::get_option ('portnumber'),
              address => Opts::get_option ('server'),
              namespace => Opts::get_option('namespace'),
              timeout  => Opts::get_option('timeout')
         );
         my $client = WSMan::StubOps->new(%args);
         display_frus($client);
  
}

sub display_frus {
   my ($client) = @_;
   my @computersystem = $client->EnumerateInstances(class_name => 'CIM_ComputerSystem');
   my @frus = $client->EnumerateInstances(class_name => 'CIM_LogicalDevice');
   print "\n";
   foreach (@computersystem){
      if($_->epr_name =~ m/unitarycomputersystem/i){
        print "Name : ", $_->ElementName,"\n";
        my $Name = $_->Name;
        foreach (@frus){
          if($_->SystemCreationClassName =~ m/unitarycomputersystem/i &&
              $_->SystemName eq $Name){
                  print "\tFRU : ", $_->ElementName, "\n";
              }
        }
      }
   }
}
