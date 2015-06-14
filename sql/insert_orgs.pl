#?/usr/bin/env perl
open F, 'orgs.tsv' or die $!;
while(<F>){
	next until ($. != 1);
	chomp;
	my @line = split "\t";
	foreach $line(@line){
		$line = "'$line'";
	}
	$line = join ",", @line;
	print "insert into Organism_info values ($line,'','');\n";
}

