#!/usr/bin/perl
use strict;
use warnings;

use WSMan::StubOps;
use VMware::VILib;

=pod
  This sample is used to get the list of firmware revisions on a system.
  
  USAGE:: perl firmwarerevisions.pl --server xyz.abc.com --username abc 
          --password xxxx
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


display_firmware_revisions();


sub display_firmware_revisions {
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
      my @SWRevs = $client->EnumerateInstances(class_name => 'CIM_SoftwareIdentity');
      print "\n";
      if(scalar(@SWRevs)){
         foreach (@SWRevs){
            if($_->Name){
               print "Name : ",$_->Name,"\n";
            }
            else{print "Name: Not Available\n";}
            if($_->Manufacturer){
               print "Manufacturer : ",$_->Manufacturer,"\n";
            }
            else{print "Manufacturer : Not Available\n";}
            if($_->VersionString){
               print "Version : ",$_->VersionString,"\n";
            }
            else{print "Version : Not Available\n";}
            if($_->ReleaseDate){
               $_->ReleaseDate=~m/(.{4})(.{2})(.{2})/;
               print "Release Date : $1\\$2\\$3\n\n";
            }
            else{print "ReleaseDate : Not Available\n\n";}
         }
      }
      else{
         print "No Software Data available on $ARGV[0]:$ARGV[2]/$ARGV[1]\n";
      }
}
