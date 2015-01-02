function gerarEmailForm(curso, alunos) {
                ids = [];
                email = [];
                $.each(alunos, function(ind, val){
                    ids.push(val.userid);
                    email.push(val.email);
                });

                var string = "<form action='email.php' method='post'>" +
                        "<input type='hidden' name='curso-tipo' value='" + curso + "'>" +
                        "<input type='hidden' name='emails[]' value='" + email + "'>" +
                        "<input type='hidden' name='ids[]' value='" + ids + "'>" +
                        "<center>" +
                        "<p><?php echo get_string('assunto','block_analytics_graphs');?>: <input type='text' name='subject' ></p>" +
                        "<textarea id='styled' cols='100' rows='6' name='texto' ></textarea>" +
                        "<br>" +
                        "<input type='submit' value='<?php echo get_string('enviar_email','block_analytics_graphs');?>' style='font-size: small' ></center>" +
                        "</form>";
                return string;
            }
