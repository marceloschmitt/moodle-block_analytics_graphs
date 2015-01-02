<?php
class graphDateOfAccess {

private $course;
private $legacy;
private $course_name;
private $startdate;
private $title;

function __construct($course, $legacy)
{
	$this->course = $course;
	$this->legacy = $legacy;
	/* Limita o acesso a quem esta autorizado por access.php */
	require_login($course);
	$context = get_context_instance(CONTEXT_COURSE, $course);
	require_capability('block/analytics_graphs:viewpages', $context);

	/* Recupera a data de inicio do curso e o nome completo */
	$course_params = get_course($course);
	$this->startdate = $course_params->startdate;
	$this->course_name = get_string('disciplina','block_analytics_graphs') . ": " . $course_params->fullname;

}

public function setTitle($name)
{  
	$this->title = $name;
}


private function getAssignSubmission($course)
{
global $DB;
        $params = array($course);
        $sql = "SELECT (@cnt := @cnt + 1) AS id, a.id AS assignment, name, duedate, cutoffdate,
                s.userid, user.firstname, user.lastname, user.email, s.timecreated
                FROM {assign} AS a
                LEFT JOIN {assign_submission} as s on a.id = s.assignment
                LEFT JOIN {user} as user ON user.id = s.userid
                CROSS JOIN (SELECT @cnt := 0) AS dummy
                WHERE course = ? and nosubmissions = 0 ORDER BY duedate, name, firstname";

        $resultado = $DB->get_records_sql($sql, $params);
        return($resultado);


}


public function createGraph()
{
global $DB;

require('lib.php');


/* Recupera os estudantes do curso */
$estudantes = getStudents($this->course);
$num_estudantes = count($estudantes);
if($num_estudantes == 0)
{       error("Não há estudantes cadastrados na disciplina!");
}
foreach($estudantes AS $tupla)
{    $vetor_estudantes[] = array('userid'=>$tupla->id , 'nome'=>$tupla->firstname.' '.$tupla->lastname, 'email'=>$tupla->email);
}


/* Recupera as tarefas submetidas */
$resultado = getAssignSubmission($this->course);


$contador = 0;
$num_submissoes = 0;
$num_submissoes_atrasadas = 0;
$num_materiais_topico = 0;
$tarefa_submetida = 0;

foreach($resultado AS $tupla)
{   
    if($tarefa_submetida == 0) /* Se ÃÂ© a primeira entrada no laÃ§o anota oÃÂ³pico e o  nome nome do material */
    {
        $estatistica[$contador]['assign'] = $tupla->name;
        $estatistica[$contador]['duedate'] = $tupla->duedate;
        $estatistica[$contador]['cutoffdate'] = $tupla->cutoffdate;
        if($tupla->userid) /* Se um aluno submeteu */
        	if($tupla->duedate >= $tupla->timecreated || $tupla->duedate == 0) /* No tempo certo */
		{
            		$estatistica[$contador]['submissoes'][] = array('userid' => $tupla->userid, 'nome' => $tupla->firstname." ".$tupla->lastname, 'email' => $tupla->email, 'timecreated' => $tupla->timecreated);
            		$num_submissoes++;
        	}
		else /* Atrasado */
		{
                        $estatistica[$contador]['submissoes_atrasadas'][] = array('userid' => $tupla->userid, 'nome' => $tupla->firstname." ".$tupla->lastname, 'email' => $tupla->email, 'timecreated' => $tupla->timecreated);
                        $num_submissoes_atrasadas++;
                }
        $tarefa_submetida = $tupla->assignment;
    }
    else /* Se nÃÂ£o ÃÂ© a primeira vez que entra no laÃÂ§o, testa se ÃÂ© o mesmterial ou material novo */
    {
        if($tarefa_submetida == $tupla->assignment and $tupla->userid)  /* Se nao mudou a tarefa,  acrescenta nome de quem acessou */
		if($tupla->duedate >= $tupla->timecreated || $tupla->duedate == 0) /* No tempo certo */
                {
                        $estatistica[$contador]['submissoes'][] = array('userid' => $tupla->userid, 'nome' => $tupla->firstname." ".$tupla->lastname, 'email' => $tupla->email, 'timecreated' => $tupla->timecreated);
                        $num_submissoes++;
                }
                else /* Atrasado */
                {
                        $estatistica[$contador]['submissoes_atrasadas'][] = array('userid' => $tupla->userid, 'nome' => $tupla->firstname." ".$tupla->lastname, 'email' => $tupla->email, 'timecreated' => $tupla->timecreated);
                        $num_submissoes_atrasadas++;
                }

        if($tarefa_submetida != $tupla->assignment) /* Se mudou material, fecha conta anterior e inicia */
        {

           	$estatistica[$contador]['num_submissoes'] = $num_submissoes;
           	$estatistica[$contador]['num_submissoes_atrasadas'] = $num_submissoes_atrasadas;
            	$estatistica[$contador]['num_nao_submissoes'] = $num_estudantes - $num_submissoes - $num_submissoes_atrasadas;
		if($estatistica[$contador]['num_nao_submissoes'] == $num_estudantes)
      			$estatistica[$contador]['nao_submissoes'] = $vetor_estudantes;
		elseif($num_submissoes_atrasadas == 0)
      			$estatistica[$contador]['nao_submissoes'] =  diferencaVetores($vetor_estudantes, $estatistica[$contador]['submissoes']);
		elseif($num_submissoes == 0)
      			$estatistica[$contador]['nao_submissoes'] =  diferencaVetores($vetor_estudantes, $estatistica[$contador]['submissoes_atrasadas']);
		else
      			$estatistica[$contador]['nao_submissoes'] =  diferencaVetores(diferencaVetores($vetor_estudantes, $estatistica[$contador]['submissoes']),$estatistica[$contador]['submissoes_atrasadas']);


            	$contador++;
		$num_submissoes = 0;
                $num_submissoes_atrasadas = 0;
 		$estatistica[$contador]['assign'] = $tupla->name;
        	$estatistica[$contador]['duedate'] = $tupla->duedate;
        	$estatistica[$contador]['cutoffdate'] = $tupla->cutoffdate;
            	$tarefa_submetida = $tupla->assignment;
            	if($tupla->userid) /* Se usuario submeteu */
            		if($tupla->duedate >= $tupla->timecreated || $tupla->duedate == 0) /* No tempo certo */
                	{
                        	$estatistica[$contador]['submissoes'][] = array('userid' => $tupla->userid, 'nome' => $tupla->firstname." ".$tupla->lastname, 'email' => $tupla->email, 'timecreated' => $tupla->timecreated);
                        	$num_submissoes=1;
                	}
                	else /* Atrasado */
                	{
                        	$estatistica[$contador]['submissoes_atrasadas'][] = array('userid' => $tupla->userid, 'nome' => $tupla->firstname." ".$tupla->lastname, 'email' => $tupla->email, 'timecreated' => $tupla->timecreated);
                        	$num_submissoes_atrasadas=1;
                	} 
        }
    }
}


/* Ajuste do ÃÂºltimo acesso  */
$estatistica[$contador]['num_submissoes'] = $num_submissoes;
$estatistica[$contador]['num_submissoes_atrasadas'] = $num_submissoes_atrasadas;
$estatistica[$contador]['num_nao_submissoes'] = $num_estudantes - $num_submissoes - $num_submissoes_atrasadas;

if($estatistica[$contador]['num_nao_submissoes'] == $num_estudantes)
      $estatistica[$contador]['nao_submissoes'] = $vetor_estudantes;
elseif($num_submissoes_atrasadas == 0)
      $estatistica[$contador]['nao_submissoes'] =  diferencaVetores($vetor_estudantes, $estatistica[$contador]['submissoes']);
elseif($num_submissoes_ == 0)
      $estatistica[$contador]['nao_submissoes'] =  diferencaVetores($vetor_estudantes, $estatistica[$contador]['submissoes_atrasadas']);
else
      $estatistica[$contador]['nao_submissoes'] =  diferencaVetores(diferencaVetores($vetor_estudantes, $estatistica[$contador]['submissoes']),$estatistica[$contador]['submissoes_atrasadas']);


/* Informações para os gráficos */
$i = 0;
//$x = 0;
$vetNomesNaoSub = array();					//declara as matrizes que serao usadas. necessario ser fora do foreach para nao ser variavel local e perder o conteudo.
$vetNomesSub = array();	
$vetNomesAtr = array();	

$vetEmailsNaoSub = array();
$vetEmailsSub = array();
$vetEmailsAtr= array();

foreach($estatistica AS $tupla)
{  
	$vetorTarefas[] = $tupla['assign'];
	$vetorSubNaData[] = $tupla['num_submissoes'];
	$vetorSubAtr[] = $tupla['num_submissoes_atrasadas'];
	$vetorNaoSub[] = $tupla['num_nao_submissoes'];
	$vetorDatas[] = $tupla['duedate'];
	$vetorDataFinal[] = $tupla['cutoffdate'];
	

if ($tupla['num_nao_submissoes'])
{	
	//declara a matriz - dimensao i se refere às barras. dimensao j se refere ao conteudo de cada barra
	$arrlength1 = count($tupla['nao_submissoes']);
	//echo "<br><br>Nao submetidas" . $i . ":<br>";
	for ($j1=0; $j1<$arrlength1; $j1++)
		{	$vetNomesNaoSub[$i][$j1] = $tupla['nao_submissoes'][$j1]['nome'];
			$vetEmailsNaoSub[$i][$j1] = $tupla['nao_submissoes'][$j1]['email'];
			//echo $vetNomesNaoSub[$i][$j1];
		}
}		
if ($tupla['num_submissoes'])
{		
	//declara a matriz - dimensao i se refere às barras. dimensao j se refere ao conteudo de cada barra
	$arrlength2 = count($tupla['submissoes']);
	//echo "<br><br>Submetidas" . $i . ":<br>";
	for ($j2=0; $j2<$arrlength2; $j2++)
		{	$vetNomesSub[$i][$j2] = $tupla['submissoes'][$j2]['nome'];
			$vetEmailsSub[$i][$j2] = $tupla['submissoes'][$j2]['email'];
			//echo $vetNomesSub[$i][$j2];
		}
}
if ($tupla['num_submissoes_atrasadas'])
{		
	//declara a matriz - dimensao i se refere às barras. dimensao j se refere ao conteudo de cada barra
	$arrlength3 = count($tupla['submissoes_atrasadas']);
	//echo "<br><br>Atrasadas" . $i . ":<br>";
	for ($j3=0; $j3<$arrlength3; $j3++)
		{	$vetNomesAtr[$i][$j3] = $tupla['submissoes_atrasadas'][$j3]['nome'];
			$vetEmailsAtr[$i][$j3] = $tupla['submissoes_atrasadas'][$j3]['email'];
			//echo $vetNomesAtr[$i][$j3];
		}
}		
		$i++;	
		
}


$estatistica = json_encode($estatistica);

?>
<!--DOCTYPE HTML-->
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <title><?php echo get_string('titulo_submissoes','block_analytics_graphs'); ?></title>
	<link rel="stylesheet" type="text/css" href="style.css">
        <link rel="stylesheet" href="http://code.jquery.com/ui/1.11.1/themes/smoothness/jquery-ui.css">
        
        <!--<script src="http://code.jquery.com/jquery-1.10.2.js"></script>-->
        <script src="http://ajax.aspnetcdn.com/ajax/jQuery/jquery-1.11.1.js"></script>
        
        <script src="http://code.jquery.com/ui/1.11.1/jquery-ui.js"></script>
        <script src="http://code.highcharts.com/highcharts.js"></script>
        <script src="http://code.highcharts.com/modules/no-data-to-display.js"></script>
        <script src="http://code.highcharts.com/modules/exporting.js"></script> 

		<script type="text/javascript">
	
            function parseObjToString(obj) {
                var array = $.map(obj, function(value) {
                    return [value];
                });
                return array;
            }
	
		//var geral = <?php echo $estatistica; ?>;
         //   geral = parseObjToString(geral);
		
		
$(function () {
    $('#container').highcharts({
        chart: {
            zoomType: 'x',
	
        },

        title: {
            text: '<?php echo get_string('titulo_submissoes','block_analytics_graphs'); ?>',
		margin: 60,
        },

 subtitle: {
                        text: ' <?php echo $this->course_name . "<br>". 
				get_string('data_de_inicio','block_analytics_graphs') . ": " . userdate($this->startdate, get_string('strftimerecentfull')); ?>',
                    },



legend: {
                        layout: 'vertical',
                        align: 'right',
                        verticalAlign: 'top',
                        x: -40,
                        y: 5,
                        floating: true,
                        borderWidth: 1,
                        backgroundColor: (Highcharts.theme && Highcharts.theme.legendBackgroundColor || '#FFFFFF'),
                        shadow: true
                    },
 credits: {
                        enabled: false
                    },

        xAxis: [{
	
		categories: [
			<?php $arrlength=count($vetorTarefas);
			for($x=0;$x < $arrlength;$x++) {
				echo "'<b>";
				echo substr($vetorTarefas[$x],0,35);
				if($vetorDatas[$x])
                                        echo "</b><br>". userdate($vetorDatas[$x],get_string('strftimerecentfull')) ."',";
                                else
                                        echo "</b><br>".get_string('sem_limite','block_analytics_graphs') . "',";
	
  			} ?> 
		],
labels: {
                rotation: -45,
	}
	}],

        yAxis: [{ // Primary yAxis
                ceiling: 1,
                min: 0,
                tickInterval: 0.25,
                labels: {
                        format: '{value}',
                        style: {
                                color: Highcharts.getOptions().colors[1]
                        }
                },

                title: {
                        text: 'Ratio',
                        style: {
                                color: Highcharts.getOptions().colors[1]
                        }
                }
        },
	{ // Primary yAxis
                min: 0,
		ceiling: <?php echo $num_estudantes; ?>,
                tickInterval: <?php echo $num_estudantes/4; ?>,
                opposite: true,
                labels: {
                        format: '{value}',
                        style: {
                                color: Highcharts.getOptions().colors[1]
                        }
                },

                title: {
                        text: '<?php echo get_string('numero_de_alunos','block_analytics_graphs');?>',
                        style: {
                                color: Highcharts.getOptions().colors[1]
                        }
                }
        }],
        tooltip: {
		 shared: true,
            crosshairs: true
        },

		
        plotOptions: {
	        series: {
                	cursor: 'pointer',
                        
			point: {
                        	events: {
					click: function() {  //Monta o evento com id da tarefa + id do comportamento
						 var nome_conteudo = this.x + "-" + this.series.index;;
                                        	$(".div_nomes").dialog("close");
                                        	$("#" + nome_conteudo).dialog("open");
					}
                                }
                            }
                        },
                        bar: {
                            dataLabels: {
                                useHTML: this,
                                enabled: true
                            }
                        }
                    },
		
		
		
		
		

        series: [{
	    yAxis: 1,
            name: '<?php echo get_string('legenda_envio_no_prazo','block_analytics_graphs'); ?>',
            type: 'column',
            data: [
			<?php $arrlength=count($vetorTarefas);
			for($x=0;$x < $arrlength ;$x++) {
  				echo $vetorSubNaData[$x];
  				echo ",";
  			} ?> 
		],
            tooltip: {
                valueSuffix: ' alunos'
            }

        },
	{
	    yAxis: 1,
            name: '<?php echo get_string('legenda_envio_fora_do_prazo','block_analytics_graphs'); ?>',
            type: 'column',
            data: [
			<?php $arrlength=count($vetorTarefas);
                        for($x=0;$x < $arrlength ;$x++) {
  				echo $vetorSubAtr[$x];
  				echo ",";
  			} ?> 
		],
            tooltip: {
                valueSuffix: ' alunos'
            }

        },
	{
	    yAxis: 1,
            name: '<?php echo get_string('legenda_sem_envio','block_analytics_graphs'); ?>',
            type: 'column',
    	color: '#FF1111',	//cor 
            data: [
			<?php $arrlength=count($vetorTarefas);
                        for($x=0;$x < $arrlength ;$x++) {
  				echo $vetorNaoSub[$x];
  				echo ",";
  			} ?> 
		],
            tooltip: {
                valueSuffix: ' alunos'
            }//1414152000

        },
	{
	    yAxis: 0,
            name: '<?php echo get_string('legenda_relacao_de_entrega','block_analytics_graphs'); ?>',
            type: 'spline',
		lineWidth: 2,
		lineColor: Highcharts.getOptions().colors[2],
            data: [
			<?php $arrlength=count($vetorTarefas);
                        for($x=0;$x < $arrlength ;$x++) {
  				printf("%.2f", ($vetorSubNaData[$x]+$vetorSubAtr[$x])/($vetorSubNaData[$x]+$vetorSubAtr[$x]+$vetorNaoSub[$x]));
  				echo ",";
  			} ?>
		],
	 marker: {
                lineWidth: 2,
                lineColor: Highcharts.getOptions().colors[2],
                fillColor: 'white'
            },
        }, 
	{
	    yAxis: 0,
            name: '<?php echo get_string('legenda_relacao_de_pontualidade','block_analytics_graphs'); ?>',
            type: 'spline',
		lineWidth: 2,
                lineColor: Highcharts.getOptions().colors[1],
            data: [
			<?php $arrlength=count($vetorTarefas);
                        for($x=0;$x < $arrlength ;$x++) {
				if($vetorDatas[$x] == 0 || $vetorDatas[$x] > time())				//se nao tiver data ou se a data é futura (nao aconteceu ainda), taxa = 1
					echo 1;
				else
  					printf ("%.2f",$vetorSubNaData[$x]/($vetorSubNaData[$x]+$vetorSubAtr[$x]+$vetorNaoSub[$x]));
  				echo ",";
  			} ?>
		],
marker: {
                lineWidth: 2,
                lineColor: Highcharts.getOptions().colors[1],
                fillColor: 'white'
	},
        }]
    });
});




		</script>
    </head>
    <body>

        <div id="container" style="min-width: 310px; min-width: 800px; height: 650px; margin: 0 auto"></div>
        <script>
            //transformamos o objeto de todos alunos em um array

	var geral = <?php echo $estatistica; ?>;
       	geral = parseObjToString(geral);
        $.each(geral, function(index, value) {
               	var nome = value.assign;
               	var nomes_submissoes = "";
               	var nomes_submissoes_atrasadas = "";
               	var nomes_nao_submissoes = "";

                if(typeof value.submissoes != 'undefined')
                    $.each(value.submissoes, function(ind, val){
                        nomes_submissoes += val.nome+", ";
                    });
                if(typeof value.submissoes_atrasadas != 'undefined')
                {
                    $.each(value.submissoes_atrasadas, function(ind, val){
                        nomes_submissoes_atrasadas += val.nome+", ";
                    });
                }
               	if(typeof value.nao_submissoes != 'undefined')
                {
                    $.each(value.nao_submissoes, function(ind, val){
                        nomes_nao_submissoes += val.nome+", ";
                    });
                }
 
                div = "";
		titulo = <?php echo json_encode($this->course_name); ?>;
                if(typeof value.submissoes != 'undefined')
                {
			titulo += "</h3>" +  <?php echo json_encode(get_string('legenda_envio_no_prazo','block_analytics_graphs')); ?> +
                            	" - " +     nome ;
                    	div += "<div class='div_nomes' id='" + index + "-0'>" + 
                            	gerarEmailForm(titulo, value.submissoes) +
                            	"</div>";
                }
                if(typeof value.submissoes_atrasadas != 'undefined')
                {
			titulo += "</h3>" +  <?php echo json_encode(get_string('legenda_envio_fora_do_prazo','block_analytics_graphs')); ?> +
                            	" - " +     nome ;
                    	div += "<div class='div_nomes' id='" + index + "-1'>" +
                        	gerarEmailForm(titulo, value.submissoes_atrasadas) +
                        	"</div>";
                }
		if(typeof value.nao_submissoes != 'undefined')
                {
			titulo += "</h3>" +  <?php echo json_encode(get_string('legenda_sem_envio','block_analytics_graphs')); ?> +
                            	" - " +     nome ;
                    	div += "<div class='div_nomes' id='" + index + "-2'>" +
                        	gerarEmailForm(titulo, value.nao_submissoes) +
                        	"</div>";
                }
                document.write(div);
            });

	enviaEmail();

        </script>
    </body>
</html>

<?php
}
}?>
