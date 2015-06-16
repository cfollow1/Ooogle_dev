function example1(){
	$('textarea[name=intervals_query]').val('');
	exampleInterval = "1:1-10000";
	$('textarea[name=intervals_query]').val(exampleInterval);
	$('textarea[name=intervals_subject]').val('');

	$('#organism_query').val('Athaliana_167');
	$('#organism_subject').val('Zmays_181');

	$('input[name=whole_genome]').prop('checked', true);
	$('#organism_subject').change();

}
function example2(){
	$('textarea[name=intervals_query]').val('');
	exampleInterval = "1:1-10000";
	$('textarea[name=intervals_query]').val(exampleInterval);
	exampleInterval = "1:1-285112815";
	$('textarea[name=intervals_subject]').val(exampleInterval);

	$('#organism_query').val('Athaliana_167');
	$('#organism_subject').val('Zmays_181');


	$('input[name=whole_genome]').prop('checked', false);
	$('#organism_subject').change();

}
