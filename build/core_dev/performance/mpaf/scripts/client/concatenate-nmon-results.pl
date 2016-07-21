#!/usr/bin/perl
use strict;
use warnings;
 
use Text::CSV_XS;
my $csv = Text::CSV_XS->new({ sep_char => ',' });
 
my $filePattern = $ARGV[0] or die "Need to specify the file pattern for which to search (e.g. web-node-1*.csv)\n";

my $headerRow = "";
my %dataRows = ();

sub UpdateHeaderRow {
	my $key = $_[0];
	my $data = $_[1];
	if (length($headerRow) > 0) {
		$headerRow = $headerRow . ",," . $data;
	} else {
		$headerRow = $key . "," . $data;
	}
}

sub UpdateDataRow {
	my $key = $_[0];
	my $data = $_[1];
	if (exists $dataRows{$key}) {
		$dataRows{$key} = $dataRows{$key} . ",," . $data;
	} else {
		$dataRows{$key} = $data;
	}
}

sub ReadFile {
	my $file = $_[0];
	open(my $fileData, '<', $file) or die "Could not open '$file' $!\n";
	my $lineCnt = 0;
	while (my $line = <$fileData>) {
		chomp $line;
		if ($csv->parse($line)) {
			my $field;
			my @fields = $csv->fields();
			my $key;
			my $rowData = '';
			my $index = 0;
			foreach $field (@fields) {
				if ($index == 0) {
					$key = $field;
				} elsif ($index == 1) {
					$rowData = $field;
				} else {
					$rowData = $rowData . "," . $field;
				}
				$index++;
			}
			if ($lineCnt == 0) {
				UpdateHeaderRow $key, $rowData
			} else {
				UpdateDataRow $key, $rowData
			}
			$lineCnt++;
		} else {
			warn "Line could not be parsed: $line\n";
		}
	}
	close($file);
}

#
# Iterate through all directories
#
my $dir;
my @dirs = grep { -d } glob '*';
foreach $dir (@dirs) {

	#
	# Find files in each directory that match the
	# input file pattern.
	#
	chdir $dir;
	my $file;
	my @files = glob "$filePattern";
	foreach $file (@files) {
		ReadFile $file;
	}
	chdir "..";
}
 
print "$headerRow\n";
foreach my $key (sort keys %dataRows) {
	print "$key,$dataRows{$key}\n";
}
