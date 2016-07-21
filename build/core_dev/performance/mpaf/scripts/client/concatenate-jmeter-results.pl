#!/usr/bin/perl
use strict;
use warnings;
use Text::CSV_XS;

use constant { true => 1, false => 0 };
 
my $filePattern = $ARGV[0] or die "Need to specify the file pattern for which to search (e.g. web-node-1*.csv)\n";
my $headerRow = "";
my @dataRows = ();

my $csv = Text::CSV_XS->new({ sep_char => ',' });

#
# Certain results will be excluded if the 'label' field starts with one or
# more specific strings implemented by this function.
#
sub excludeLine {
	my $label = $_[0];
	#  skip requests with labels starting with "Get argument", these should not be included in stats
	if ($label =~ /^Get arguments.*$/) {
		return true;
	}
	#  skip rows with labels equal to "Total", this is an artifact of jtl results analyzer
	if ($label =~ /^Total$/) {
		return true;
	}
	return false;
}

sub ReadFile {
	my $file = $_[0];
	open(my $fileData, '<', $file) or die "Could not open '$file' $!\n";
	my $lineCnt = 0;
	my $labelIndex = -1;
	while (my $line = <$fileData>) {
		chomp $line;
		if ($csv->parse($line)) {
			my $rowData = '';
			my $index = 0;
			my $label = '';
			my @fields = $csv->fields();
			foreach my $field (@fields) {
				if ($lineCnt == 0) {
					# If processing header line, remember which index corresponds to
					# the "label" field.
					if ($field eq 'label') {
						$labelIndex = $index;
					}
				} elsif (($labelIndex >= 0) && ($index == $labelIndex)) {
					$label = $field;
				}
				if ($index > 0) {
					$rowData = $rowData . ",";
				}
				$rowData = $rowData . $field;
				$index++;
			}
			if ($lineCnt == 0) {
				$headerRow = $rowData;
			} elsif ((length($label) == 0) || !excludeLine($label)) {
				push(@dataRows,$rowData);
			}
			$lineCnt++;
		} else {
			warn "Line could not be parsed: $line\n";
		}
	}
	close($file);
}

#
# ====
# Main
# ====
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
		push(@dataRows,'');
	}
	chdir "..";
}
 
print "$headerRow\n";
foreach my $dataRow (@dataRows) {
	print "$dataRow\n";
}
