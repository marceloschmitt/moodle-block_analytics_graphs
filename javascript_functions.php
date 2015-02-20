<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.



?>

<script type="text/javascript">
	
function sendEmail() {
		$( "form" ).submit(function( event ) {
                    // Stop form from submitting normally
                    event.preventDefault();
                    // Get some values from elements on the page:
                    var $form = $( this ),
                    otherval = $form.find( "input[name='other']" ).val(),
                    emailsval = $form.find( "input[name='emails[]']" ).val(),
                    idsval = $form.find( "input[name='ids[]']" ).val(),
                    subjectval = $form.find( "input[name='subject']" ).val(),
                    textoval = $form.find( "textarea[name='texto']" ).val(),
                    url = $form.attr( "action" );

                    //console.log(emailsval);
                    // Send the data using post
                    var posting = $.post( url, { other: otherval, ids: idsval, emails: emailsval, subject: subjectval, texto: textoval } );
                    // Put the results in a div
                    posting.done(function( data ) {
                    //alert(data);
                    if(data){
                        $(".div_nomes").dialog("close");
                        alert("<?php echo get_string('sent_message', 'block_analytics_graphs');?>");
                    } else {
                        alert("<?php echo get_string('not_sent_message', 'block_analytics_graphs');?>");
                    }

                });
            });

            $(".div_nomes").dialog({
                modal: true,
                autoOpen: false,
                width: 'auto'
            });
}

function createEmailForm(titulo, alunos, course, other) {
		var nomes="";
                ids = [];
                email = [];
                $.each(alunos, function(ind, val){
			nomes += val.nome + ", ";
                    	ids.push(val.userid);
                    	email.push(val.email);
                });
                var string =
			"<h3>" + titulo + "</h3>" +  
			"<p style='font-size:small'>" + nomes + "</p>" +
			"<form action='email.php?id=" + course + "' method='post'>" +
			            "<input type='hidden' name='other' value='" + other + "'>" +
                        "<input type='hidden' name='emails[]' value='" + email + "'>" +
                        "<input type='hidden' name='ids[]' value='" + ids + "'>" +
                        "<center>" +
                        "<p style='font-size:small'><?php echo get_string('subject', 'block_analytics_graphs');?>: " +
			"<input type='text' name='subject' ></p>" +
                        "<textarea style='font-size:small' cols='100' rows='6' name='texto' ></textarea>" +
                        "<br>" +
                        "<input type='submit' " +
			"value='<?php echo get_string('send_email', 'block_analytics_graphs');?>' " +
			"style='font-size: small' ></center>" +
                        "</form>";
                return string;
}

</script>

