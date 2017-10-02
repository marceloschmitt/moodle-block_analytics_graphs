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

defined('MOODLE_INTERNAL') || die();
?>

<script type="text/javascript">
    
function parseObjToString(obj) {
            var array = $.map(obj, function(value) {
                return [value];
            });
            return array;
        }

function sendEmail() {
        $( "form" ).submit(function( event ) {
                    // Stop form from submitting normally
                    event.preventDefault();
                    // Get some values from elements on the page:
                    var $form = $( this ),
                    otherval = $form.find( "input[name='other']" ).val(),
                    idsval = $form.find( "input[name='ids[]']" ).val(),
                    subjectval = $form.find( "input[name='subject']" ).val(),
                    textoval = $form.find( "textarea[name='texto']" ).val(),
                    url = $form.attr( "action" ),

                    ccteachers = $form.find( "input[name='ccteachers']" ).is(':checked');
                    // Send the data using post
                    var posting = $.post( url, { other: otherval, ids: idsval,  
                                    subject: subjectval, texto: textoval, ccteachers: ccteachers } );
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

function createEmailForm(titulo, alunos, courseid, other, subject) {
        if (!subject) { //if undefined or null then set to default value
            subject = "";
        }
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
            "<form action='email.php?id=" + courseid + "' method='post'>" +
                        "<input type='hidden' name='other' value='" + other + "'>" +
                        "<input type='hidden' name='ids[]' value='" + ids + "'>" +
                        "<center>" +
                        "<p style='font-size:small'><?php echo get_string('subject', 'block_analytics_graphs');?>: " +
                        "<input type='text' name='subject' value='" + subject +"'></p>" +
                        "<textarea style='font-size:small' cols='100' rows='6' name='texto' ></textarea>" +
                        "<br>" +
                        "<input type='checkbox' name='ccteachers' checked>" +
                        "<?php echo get_string('lbl_ccteachers', 'block_analytics_graphs');?>" +
                        "<br>" +
                        "<input type='submit' " +
            "value='<?php echo get_string('send_email', 'block_analytics_graphs');?>' " +
            "style='font-size: small' ></center>" +
                        "</form>";

                return string;
}


function convert_series_to_group(group_id, groups, all_content, chart_id)
{
    
    $(chart_id).highcharts().series[0].setData([0]);
    $(chart_id).highcharts().series[1].setData([0]);

    //comeback to original series
    if(group_id == "-")
    {
        var nraccess_vet = [];
        var nrntaccess_vet = [];
        $.each(geral, function(index, value) {
            if (value.numberofaccesses > 0){
                nraccess_vet.push(value.numberofaccesses);
            }else{
                nraccess_vet.push([0]);
            }

            if(value.numberofnoaccess > 0){
                nrntaccess_vet.push(value.numberofnoaccess);
            }else{
                nrntaccess_vet.push([0]);
            }
        });

        $(chart_id).highcharts().series[0].setData(nraccess_vet);
        $(chart_id).highcharts().series[1].setData(nrntaccess_vet);
    }
    else
    {
        $.each(groups, function(index, group){
            if(index == group_id){
                var access = group.numberofaccesses;
                var noaccess = group.numberofnoaccess;
                $(chart_id).highcharts().series[0].setData(access);
                $(chart_id).highcharts().series[1].setData(noaccess);
            }
        });
    }
}

</script>