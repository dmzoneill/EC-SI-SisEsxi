#!/usr/bin/perl
use strict;

use WSMan::StubOps;
use VMware::VILib;

=pod
  This sample is used to check the list of all the sensors associated with all
  the devices on a system.
  
  USAGE:: perl firmwarerevisions.pl --server xyz.abc.com --username abc 
          --password xxxx
=cut

$Util::script_version = "1.0";

#========================Common=======================================
my @sensortype = ("Unknown", "Other", "Temperature", "Voltage",
                  "Current", "Tachometer", "Counter", "Switch", "Lock",
                  "Humidity", "Smoke Detection", "Presence", "Air Flow");

my @availability = ("Other", "Unknown", "Running/Full Power", "Warning",
                    "In Test", "Not Applicable", "Power Off", "Off Line",
                    "Off Duty", "Degraded", "Not Installed", "Install Error",
                    "Power Save - Unknown", "Power Save - Low Power Mode",
                    "Power Save - Standby", "Power Cycle",
                    "Power Save - Warning", "Paused", "Not Ready",
                    "Not Configured", "Quiesced");

my @operationalstatus = ("Unknown", "Other", "OK", "Degraded", "Stressed",
                         "Predictive Failure", "Error", "Non-Recoverable Error",
                         "Starting", "Stopping", "Stopped", "In Service",
                         "No Contact", "Lost Communication", "Aborted", "Dormant",
                         "Supporting Entity in Error", "Completed", "Power Mode",
                         "DMTF Reserved", "Vendor Reserved");

my %healthstatus=(0 => "Unknown", 5 => "OK",
                  10 => "Degraded/Warning",
                  15 => "Minor failure",
                  20 => "Major failure",
                  25 => "Critical failure",
                  30 => "Non-recoverable error");
#===========================Numeric Sensors===================================
my @modelcorrespondence = ("Unknown", "Other", "Degrees C", "Degrees F",
          "Degrees K", "Volts", "Amps", "Watts", "Joules", "Coulombs",
          "VA", "Nits", "Lumens", "Lux", "Candelas", "kPa", "PSI",
          "Newtons", "CFM", "RPM",
          "Hertz", "Seconds", "Minutes", "Hours", "Days", "Weeks",
          "Mils", "Inches", "Feet", "Cubic Inches",
          "Cubic Feet", "Meters", "Cubic Centimeters", "Cubic Meters",
          "Liters", "Fluid Ounces", "Radians", "Steradians",
          "Revolutions", "Cycles",
          "Gravities", "Ounces", "Pounds", "Foot-Pounds",
          "Ounce-Inches", "Gauss", "Gilberts", "Henries", "Farads",
          "Ohms",
          "Siemens", "Moles", "Becquerels", "PPM (parts/million)",
          "Decibels", "DbA", "DbC", "Grays", "Sieverts",
          "Color Temperature Degrees K",
          "Bits", "Bytes", "Words (data)", "DoubleWords", "QuadWords",
          "Percentage");

my @rateunit = ("None", "Per MicroSecond", "Per MilliSecond",
                "Per Second", "Per Minute", "Per Hour", "Per Day",
                "Per Week", "Per Month", "Per Year");


my @excludesensors;

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
      display_sensor_health_status($client);
}

sub display_sensor_health_status {
   my ($client) = @_;
   my @managedelements = $client->EnumerateInstances(class_name =>'CIM_SystemDevice');
   my @sensors = $client->EnumerateInstances(class_name => 'CIM_Sensor');
   print "\n";
   foreach (@managedelements){
      if($_->PartComponent->epr_name !~m/sensor/i){
         my $instance = $client->GetInstance(objectpath => $_->PartComponent);
         if (exists $instance->{ElementName}){
            print "Name : ", $instance->{ElementName}, "\n";
         }
         elsif(exists $instance->{Name}){
            print "Name : ", $instance->{Name}, "\n";
         }
         elsif(exists $instance->{Caption}){
            print "Name : ", $instance->{Caption}, "\n";
         }
         else{
            my @name = split /_/, $instance->epr_name;
            (print "Name : $name[-1] \n")
         };
         if(exists $instance->{HealthState}){
            if(exists $healthstatus{$instance->{HealthState}}){
               print "Health : ", $healthstatus{$instance->{HealthState}}, "\n";
            }
            else {print "Health : Unknown\n"};
         }
         else {print "Health : Unknown\n"};
         if(exists $instance->{OperationalStatus}){
            if($instance->OperationalStatus > (scalar(@operationalstatus)-1)){
               print "OperationalStatus : Unknown\n";
            }
            else{
               print "OperationalStatus : ",
                      $operationalstatus[$instance->OperationalStatus], "\n";
            }
         }
         else{print "OperationalStatus : Unknown\n";}
         print "\n";

        my @associatedsensors = $client->EnumerateAssociatedInstances(
          objectpath => $_->PartComponent,
          associationclassname => 'CIM_AssociatedSensor'
        );
        foreach (@associatedsensors){
          display_info($_);
        }
        push @excludesensors,@associatedsensors;
      }
   }
   my %sensors = map {$_->DeviceID => $_} @sensors;
   foreach (@excludesensors){
      delete $sensors{$_->DeviceID};
   }
   print "Miscellaneous Sensors:\n\n";
   foreach (values %sensors){
      display_info($_);
   }
}

sub display_info {
   my ($sen) = @_;
   if ($sen->ElementName){
      print "\tName : ", $sen->ElementName, "\n";
   }
   elsif($sen->Name){
      print "\tName : ", $sen->Name, "\n";
   }
   elsif($sen->Caption){
      print "\tName : ", $sen->Caption, "\n";
   }
   else{
      my @name = split /_/, $sen->epr_name;
      (print "\tName : $name[-1] \n")
   };
   if ($sen->SensorType < scalar (@sensortype)){
      print "\tSensorType : ", $sensortype[$sen->SensorType], "\n";
   }
   else{ (print "\tSensorType : undef\n")};
   if($sen->HealthState){
      if(exists $healthstatus{$sen->HealthState}){
         print "\tHealth : ", $healthstatus{$sen->HealthState}, "\n";
      }
      else {print "\tHealth : Unknown\n"};
   }
   else {print "\tHealth : Unknown\n"};
   if ($sen->OperationalStatus < scalar @operationalstatus){
      print "\tOperationalStatus  : ",
            $operationalstatus[$sen->OperationalStatus]
            , "\n";
   }
   else{ (print "\tOperationalStatus : undef\n")};
   
   if($sen->epr_name =~ m/numericsensor/i){
      if($sen->CurrentReading){
         my @cr = split, $sen->CurrentReading;
         my $currentreading = $sen->CurrentReading;
         if($sen->BaseUnits < scalar(@modelcorrespondence)){
            if($sen->UnitModifier){
               $currentreading = $currentreading*(10**$sen->UnitModifier);
            }
            $currentreading = $currentreading.' '.$modelcorrespondence[$sen->BaseUnits];
            if($sen->RateUnits< scalar(@rateunit)-1 && $sen->RateUnits != 0){
               $currentreading .= ' '.$rateunit[$sen->RateUnits];
           }
        }
        print "\tCurrent Reading: ", $currentreading,"\n";
      }
   }
   print "\n";
}

