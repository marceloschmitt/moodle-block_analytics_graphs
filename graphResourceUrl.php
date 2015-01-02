<?php

require('../../config.php');
require('lib.php');
require('javascript_functions.php');
$course = required_param('id', PARAM_INT);
$legacy = required_param('legacy', PARAM_INT);
global $DB;

$titulo = get_string('title_one','block_analytics_graphs');
$legenda_acessaram = get_string('legenda_acessaram','block_analytics_graphs');
$legenda_nao_acessaram = get_string('legenda_nao_acessaram','block_analytics_graphs');;
$legenda_material = get_string('material','block_analytics_graphs');
$legenda_alunos = get_string('numero_de_alunos','block_analytics_graphs');

/* Limita o acesso a quem esta autorizado por access.php */
require_login($course);
$context = get_context_instance(CONTEXT_COURSE, $course);
require_capability('block/analytics_graphs:viewpages', $context);


/* Recupera a data de inicio do curso e o nome completo */
$course_params = get_course($course);
$startdate = $course_params->startdate;
$nome_do_curso = get_string('disciplina','block_analytics_graphs') . ": " . $course_params->fullname;


/* Recupera os estudantes do curso */
$estudantes = getStudents($course);



$num_estudantes = count($estudantes);
if($num_estudantes == 0)
{       error("Não há estudantes cadastrados na disciplina!");
}

foreach($estudantes AS $tupla)
{    $vetor_estudantes[] = array('userid'=>$tupla->id , 'nome'=>$tupla->firstname.' '.$tupla->lastname, 'email'=>$tupla->email);
}

/* Recupera os acessos aos recursos e URLs */
$resultado = getResourceAndUrlAccess($course,$estudantes,$legacy);

$contador = 0;
$num_acessos = 0;
$num_materiais_topico = 0;
$material_acessado = 0;
$num_materiais_topico = array();

foreach($resultado AS $tupla)
{   
    if($material_acessado == 0) /* Se ÃÂ© a primeira entrada no laÃ§o anota oÃÂ³pico e o  nome nome do material */
    {
	$num_materiais_topico[$tupla->section] = 1;
        $estatistica[$contador]['topico'] = $tupla->section;
        $estatistica[$contador]['tipo'] = $tupla->tipo;
        if($tupla->tipo == 'resource')
                $estatistica[$contador]['material'] = $tupla->resource;
        else
                $estatistica[$contador]['material'] = $tupla->url;
        if($tupla->userid) /* Se um usuÃÂ¡rio acessou, anota o nome */
        {
            $estatistica[$contador]['acessos'][] = array('userid' => $tupla->userid, 'nome' => $tupla->firstname." ".$tupla->lastname, 'email' => $tupla->email);
            $num_acessos++;
        }
        $material_acessado = $tupla->ident;
    }
    else /* Se nÃÂ£o ÃÂ© a primeira vez que entra no laÃÂ§o, testa se ÃÂ© o mesmterial ou material novo */
    {
        if($material_acessado == $tupla->ident and $tupla->userid)  /* Se nÃÂ£o mudou material e houve acesso, acrescenta nome de quem acessou */
        {
            $estatistica[$contador]['acessos'][] = array('userid' => $tupla->userid, 'nome' => $tupla->firstname." ".$tupla->lastname, 'email' => $tupla->email);
            $num_acessos++;
        }
        if($material_acessado != $tupla->ident) /* Se mudou material, acrescenta nÃÂºmero de acessos, nomes de quem nÃÂ£o acessou e cria material novo (tÃco e nome) */
        {
	    if($estatistica[$contador]['topico'] == $tupla->section)
		$num_materiais_topico[$tupla->section]++;
	    else
		$num_materiais_topico[$tupla->section] = 1;

            $estatistica[$contador]['num_acessos'] = $num_acessos;
            $estatistica[$contador]['num_nao_acessos'] = $num_estudantes - $num_acessos;
            if($num_acessos == 0)
                $estatistica[$contador]['nao_acessos'] = $vetor_estudantes;
            elseif($estatistica[$contador]['num_nao_acessos'] > 0)
               $estatistica[$contador]['nao_acessos'] =  diferencaVetores($vetor_estudantes, $estatistica[$contador]['acessos']);

            $contador++;
            $estatistica[$contador]['topico'] = $tupla->section;
            $estatistica[$contador]['tipo'] = $tupla->tipo;
            $material_acessado = $tupla->ident;

            if($tupla->tipo == 'resource')
                $estatistica[$contador]['material'] = $tupla->resource;
            else
                $estatistica[$contador]['material'] = $tupla->url;

            if($tupla->userid) /* Se usuÃÂ¡rio acessou, anota nome */
            {
                $estatistica[$contador]['acessos'][] = array('userid' => $tupla->userid, 'nome' => $tupla->firstname." ".$tupla->lastname, 'email' => $tupla->email);
                $num_acessos = 1;
            }
            else
                $num_acessos = 0;
        }
    }
}

/* Ajuste do ÃÂºltimo acesso  */

            if($estatistica[$contador]['topico'] == $tupla->section)
                $num_materiais_topico[$tupla->section]++;
            else
                $num_materiais_topico[$tupla->section] = 1;


$estatistica[$contador]['num_acessos'] = $num_acessos;
$estatistica[$contador]['num_nao_acessos'] = $num_estudantes - $num_acessos;
if($num_acessos == 0)
	$estatistica[$contador]['nao_acessos'] = $vetor_estudantes;
elseif ($estatistica[$contador]['num_nao_acessos'] > 0)
	$estatistica[$contador]['nao_acessos'] =  diferencaVetores($vetor_estudantes, $estatistica[$contador]['acessos']);



$estatistica = json_encode($estatistica);


?>
<!--DOCTYPE HTML-->
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <title>Chart 1</title>
	<link rel="stylesheet" type="text/css" href="style.css">
        <link rel="stylesheet" href="http://code.jquery.com/ui/1.11.1/themes/smoothness/jquery-ui.css">
        
        <!--<script src="http://code.jquery.com/jquery-1.10.2.js"></script>-->
        <script src="http://ajax.aspnetcdn.com/ajax/jQuery/jquery-1.11.1.js"></script>
        
        <script src="http://code.jquery.com/ui/1.11.1/jquery-ui.js"></script>
        <script src="http://code.highcharts.com/highcharts.js"></script>
        <script src="http://code.highcharts.com/modules/no-data-to-display.js"></script>
        <script src="http://code.highcharts.com/modules/exporting.js"></script> 


        <script type="text/javascript">
            var geral = <?php echo $estatistica; ?>;
            geral = parseObjToString(geral);

            var nomeCursos = [];

            var arrayCursoAlunos = [];

            var acessaram = [];
            var naoAcessaram = [];
            var nome = "";
            var recursoAnterior = "";

            
            var nomes_vet = [];
            var nraccess_vet = [];
            var nrntaccess_vet = [];

            $.each(geral, function(index, value) {
                if(value.num_acessos > 0 || value.num_nao_acessos > 0)
                {
                    var nome = value.material;
		    nomes_vet.push(nome);
                    nraccess_vet.push(value.num_acessos);
                    nrntaccess_vet.push(value.num_nao_acessos);
                }
            });


            function parseObjToString(obj) {
                var array = $.map(obj, function(value) {
                    return [value];
                });
                return array;
            }

            $(function() {
                $('#container').highcharts({
                    chart: {
                        type: 'bar',
                        zoomType: 'x',
			panning: true,
			panKey: 'shift'
                    },
                    title: {
                        text: ' <?php echo $titulo; ?>'
                    },
                    subtitle: {
                        text: ' <?php echo $nome_do_curso . "<br>".
				get_string('data_de_inicio','block_analytics_graphs') . ": " . userdate($startdate); ?>'
                    },
                    xAxis: {
                        minRange: 1,
                        categories: nomes_vet,
                        title: {
                            text: '<?php echo $legenda_material; ?>'
                        },
		
			plotBands: [
				<?php
				$inicio = -0.5;
				$par = 2;
				foreach($num_materiais_topico AS $topico => $num_topicos)
				{	$fim = $inicio + $num_topicos;
				?>		
					{
    						color: '<?php if($par%2) echo 'rgba(0, 0, 0, 0)'; else echo 'rgba(68, 170, 213, 0.1)';?>', // Color value
 						label: {
                     					align: 'right',
							x: -10,
							verticalAlign: 'middle' ,
                    					text: 'Seção <?php if($topico) echo " $topico"; else echo " inicial";?>' ,
                    					style: {
								fontStyle: 'italic',
                    					}
                				},
    						from: '<?php echo $inicio;?>', // Start of the plot band
    						to: '<?php echo $fim;?>', // End of the plot band
					},
					<?php
					$inicio = $fim;
					$par++;
				}
				?>
			]

                    },
                    yAxis: {
                        min: 0,
			maxPadding: 0.1,
                        minTickInterval: 1,
                        title: {
                            text: '<?php echo $legenda_alunos; ?>',
                            align: 'high'
                        },
                        labels: {
                            overflow: 'justify'
                        }
                    },
                    tooltip: {
                        valueSuffix: ' alunos'
                    },
                    plotOptions: {
                        series: {
                            cursor: 'pointer',
                            point: {
                                events: {
                                    click: function() {
                                        var nome_conteudo = nomes_vet.indexOf(this.category) + "-" + this.series.name.charAt(0);
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
                    series: [{
                            name: '<?php echo $legenda_acessaram; ?>',
                            data: nraccess_vet,
                            color: '#82FA58'
                        }, {
                            name: '<?php echo $legenda_nao_acessaram; ?>',
                            label: '<?php echo "oi"; ?>',
                            data: nrntaccess_vet,
                            color: '#FE2E2E'
                        }]
                });
            });

        </script>
    </head>
    <body>

        <div id="container" style="min-width: 310px; min-width: 800px; min-height: 600px; margin: 0 auto"></div>
        <script>
            $.each(geral, function(index, value) {
                var nome = value.material;
                div = "";
                if(typeof value.acessos != 'undefined')
                {
                    div += "<div class='div_nomes' id='" + nomes_vet.indexOf(nome) + "-" + 
			    "<?php echo substr(get_string('legenda_acessaram','block_analytics_graphs'),0,1);?>" +
                            "'><h3> <?php echo get_string('legenda_acessaram','block_analytics_graphs'); ?> " +
			    " - " +	nome + "</h3>" +
                            gerarEmailForm(nomes_vet.indexOf(nome), value.acessos) +
                            "</div>";
                }
                if(typeof value.nao_acessos != 'undefined')
                {
                    div += "<div class='div_nomes' id='" + nomes_vet.indexOf(nome) + "-" +
                        "<?php echo substr(get_string('legenda_nao_acessaram','block_analytics_graphs'),0,1);?>" +
                        "'><h3> <?php echo get_string('legenda_nao_acessaram','block_analytics_graphs'); ?> " +
			 " - " +     nome + "</h3>" +
                        gerarEmailForm(nomes_vet.indexOf(nome), value.nao_acessos) +
                        "</div>";
                }
                document.write(div);
            });

	    enviaEmail();

        </script>
    </body>
</html>
