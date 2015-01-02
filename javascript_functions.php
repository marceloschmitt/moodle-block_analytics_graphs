<script type="text/javascript">
	
function enviaEmail() {
		$( "form" ).submit(function( event ) {
                    // Stop form from submitting normally
                    event.preventDefault();
                    // Get some values from elements on the page:
                    var $form = $( this ),
                    emailsval = $form.find( "input[name='emails[]']" ).val(),
                    idsval = $form.find( "input[name='ids[]']" ).val(),
                    subjectval = $form.find( "input[name='subject']" ).val(),
                    textoval = $form.find( "textarea[name='texto']" ).val(),
                    url = $form.attr( "action" );

                    //console.log(emailsval);
                    // Send the data using post
                    var posting = $.post( url, { ids: idsval, emails: emailsval, subject: subjectval, texto: textoval } );
                    // Put the results in a div
                    posting.done(function( data ) {
                    //alert(data);
                    if(data){
                        $(".div_nomes").dialog("close");
                        alert("<?php echo get_string('mensagem_enviada','block_analytics_graphs');?>");
                    } else {
                        alert("<?php echo get_string('mensagem_nao_enviada','block_analytics_graphs');?>");
                    }

                });
            });

            $(".div_nomes").dialog({
                modal: true,
                autoOpen: false,
                width: 'auto'
            });
}

function gerarEmailForm(titulo, alunos) {
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
			"<form action='email.php' method='post'>" +
                        "<input type='hidden' name='emails[]' value='" + email + "'>" +
                        "<input type='hidden' name='ids[]' value='" + ids + "'>" +
                        "<center>" +
                        "<p style='font-size:small'><?php echo get_string('assunto','block_analytics_graphs');?>: <input type='text' name='subject' ></p>" +
                        "<textarea style='font-size:small' cols='100' rows='6' name='texto' ></textarea>" +
                        "<br>" +
                        "<input type='submit' value='<?php echo get_string('enviar_email','block_analytics_graphs');?>' style='font-size: small' ></center>" +
                        "</form>";
                return string;
}

</script>

