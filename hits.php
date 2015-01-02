<?php

require('../../config.php');
require('lib.php');
require('javascript_functions.php');
$course = required_param('id', PARAM_INT);
$legacy = required_param('legacy', PARAM_INT);
global $DB;

/* Limita o acesso a quem esta autorizado por access.php */
require_login($course);
$context = get_context_instance(CONTEXT_COURSE, $course);
require_capability('block/analytics_graphs:viewpages', $context);

/* Recupera a data de inicio do curso e o nome completo */
$course_params = get_course($course);
$startdate = $course_params->startdate;
$nome_do_curso =  get_string('disciplina','block_analytics_graphs') . ": " . $course_params->fullname;

/* Recupera os estudantes do curso */
$estudantes = getStudents($course);
$num_estudantes = count($estudantes);
if($num_estudantes == 0)
{       error("NÃÂ£o hÃÂ¡ estudantes cadastrados na disciplina!");
}
foreach($estudantes AS $tupla)
{
        $vetor_estudantes[] = array('userid'=>$tupla->id , 'nome'=>$tupla->firstname.' '.$tupla->lastname, 'email'=>$tupla->email);
}

/* Recupera os acessos ao curso */
$resultado = getCourseDayAcessByWeek($course,$estudantes,$startdate);

/* Recupera alunos que nunca acesssaram */
$maiorNumeroSemanas = 0;
foreach($resultado AS $tupla)
{
        $vetor_acessos[] = array('userid'=>$tupla->userid , 'nome'=>$tupla->firstname.' '.$tupla->lastname, 'email'=>$tupla->email);
        if($tupla->week > $maiorNumeroSemanas)
                $maiorNumeroSemanas = $tupla->week;
}
$alunosSemAcesso = diferencaVetores($vetor_estudantes, $vetor_acessos);
$alunosSemAcesso = json_encode($alunosSemAcesso);

/* Recupera os acessos aos materiais do curso */
$resultadoAcessos = getCourseModuleDayAcessByWeek($course,$estudantes,$startdate);
$maiorNumeroRecursos = 0;
foreach($resultadoAcessos AS $tupla)
        if( $tupla->number > $maiorNumeroRecursos)
                $maiorNumeroRecursos = $tupla->number;
$resultadoNumeroDeRecursos = getCourseNumberOfModulesAccessed($course,$estudantes,$startdate);

/* Ajusta os dados para javascript */
$resultado = json_encode($resultado);
$resultadoAcessos = json_encode($resultadoAcessos);
$resultadoNumeroDeRecursos = json_encode($resultadoNumeroDeRecursos);

?>




<html>
<head>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Chart 1</title>
<link rel="stylesheet" href="http://code.jquery.com/ui/1.11.1/themes/smoothness/jquery-ui.css">

<!--<script src="http://code.jquery.com/jquery-1.10.2.js"></script>-->
<script src="http://ajax.aspnetcdn.com/ajax/jQuery/jquery-1.11.1.js"></script>

<script src="http://code.jquery.com/ui/1.11.1/jquery-ui.js"></script>
<script src="http://code.highcharts.com/highcharts.js"></script>
<script src="http://code.highcharts.com/modules/no-data-to-display.js"></script>
<!--<script src="http://code.highcharts.com/modules/exporting.js"></script> -->

<style>
#result {
        text-align: right;
        color: gray;
        min-height: 2em;
}
#table-sparkline {
        margin: 0 auto;
    	border-collapse: collapse;
}
th {
    font-weight: bold;
    text-align: left;
}
td, th {
    padding: 5px;
    border-bottom: 1px solid silver;
    border-right: 1px solid silver;
    height: 60px;
}
thead th {
    border-top: 2px solid gray;
    border-bottom: 2px solid gray;
}
.highcharts-tooltip>span {
        background: white;
        border: 1px solid silver;
        border-radius: 3px;
        box-shadow: 1px 1px 2px #888;
        padding: 8px;
}
</style>


<script type="text/javascript">
    var nome_do_curso = <?php echo json_encode($nome_do_curso); ?>;
    var geral = <?php echo $resultado; ?>;
    var geralAcessoModulo = <?php echo $resultadoAcessos; ?>;
    var geralNumeroDeRecursos = <?php echo $resultadoNumeroDeRecursos; ?>;
    var alunosSemAcesso = <?php echo $alunosSemAcesso; ?>;


    var nomes = [];
    $.each(geral, function(ind, val){   
        var nome = val.firstname+" "+val.lastname;
        if(nomes.indexOf(nome) === -1)
            nomes.push(nome);

    });
    
    nomes.sort();

    var alunos = [];
    //segundo grafico numero de acessos por semana
    $.each(geral, function(ind, val){
        if(alunos[val.userid]){
            var aluno = alunos[val.userid];
            aluno.semanas[val.week] = Number(val.week);
            aluno.acessos[val.week] = Number(val.number);
            aluno.totalAcessos += Number(val.number);
            aluno.pageViews += Number(val.numberofpageviews);
            alunos[val.userid] = aluno;
        }else{
            //nessa parte criamos um obj que contera um array com a semana (indice) e outro com o number (valor) os dois tendo a mesma chave que ÃÂ© o numero dasemana
            var aluno = {};
            aluno.userid = Number(val.userid);
            aluno.nome = val.firstname+" "+val.lastname;
            aluno.email = val.email;
            aluno.semanas = [];
            aluno.semanas[val.week] = Number(val.week);
            aluno.acessos = [];
            aluno.acessos[val.week] = Number(val.number);
            aluno.totalAcessos = Number(val.number);
            aluno.pageViews = Number(val.numberofpageviews);
	    if(geralNumeroDeRecursos[val.userid])
		 aluno.totalRecursos = geralNumeroDeRecursos[val.userid].number ;
	    else
		 aluno.totalRecursos = 0;
            alunos[val.userid] = aluno;
        }
    });

    $.each(geralAcessoModulo, function(index, value){
        if(alunos[value.userid]){
                var aluno = alunos[value.userid];
                
                if(aluno.semanasModulos === undefined)                        aluno.semanasModulos = [];                
                aluno.semanasModulos[value.week] = Number(value.week);
            
            if(aluno.acessosModulos === undefined)
                        aluno.acessosModulos = [];
                            aluno.acessosModulos[value.week] = (value.number>0 ? Number(value.number) : 0 );
                        

            alunos[value.userid] = aluno;
        }
    });

    function trata_array(array){
        var novo = [];
        $.each(array, function(ind, value){
                if(!value)
                        novo[ind] = 0;
                else
                        novo[ind] = value;
        });
        return novo;
    }

    function gerar_grafico_modulos(aluno){
        if(aluno.acessosModulos !== undefined){
                $("#modulos-"+aluno.userid).highcharts({

                chart: {                        borderWidth: 0,
                        type: 'area',
                        margin: [2, 0,2, 0],
                        width: 250,
                        height: 60,
                        style: {
                                overflow: 'visible'
                        },
                        skipClone: true,
                },

                xAxis: {
                        labels: {
                                enabled: false
                        },
                        title: {
                                text: null
                        },
                        startOnTick: false,
                        endOnTick: false,
                        tickPositions: [],
			max: <?php echo $maiorNumeroSemanas; ?>
                 },
                
                yAxis: {
                        minorTickInterval: 5,
                        endOnTick: false,
                        startOnTick: false,
                        labels: {
                                enabled: false
                        },
                        title: {
                                text: null
                        },
                        tickPositions: [0],
			max: <?php echo $maiorNumeroRecursos;?>,
                        tickInterval: 5
                },

                title: {
                text: null
                },

                credits: {
                          enabled: false
                 },

                legend: {
                        enabled: false
                },

                tooltip: {
                        backgroundColor: null,
                        borderWidth: 0,
                        shadow: false,
                        useHTML: true,
                        hideDelay: 0,
                        shared: true,
                        padding: 0,
                        headerFormat: '',
                        pointFormat: <?php echo "'".get_string('numero_da_semana','block_analytics_graphs').": '"; ?> +
                                        '{point.x}<br>' +  
                                        <?php echo "'".get_string('materiais_acessados','block_analytics_graphs').": '"; ?> +
                                        '{point.y}',
                        positioner: function (w, h, point) {                                return { x: point.plotX - w / 2, y: point.plotY - h};
                        }
                },
                plotOptions: {
                        series: {
                                animation:  { 
                                        duration: 4000
                                },
                                lineWidth: 1,
                                shadow: false,
                                states: {
                                        hover: {
                                                lineWidth: 1
                                                }
                                        },
                                marker: {
                                        radius: 2,
                                        states: {
                                                hover: {
                                                        radius: 4
                                                        }
                                                }                                        },
                                fillOpacity: 0.25
                        },
                },
                series: [{
                    //data: trata_array(aluno.acessos)
                    data: trata_array(aluno.acessosModulos)
                    
                }]
                    });
                }else{
                        $("#modulos-"+aluno.userid).text(":(");
                }
        }

    function gerar_grafico(aluno){
        $("#acessos-"+aluno.userid).highcharts({

                chart: {
                        borderWidth: 0,
                        type: 'area',
                        margin: [2, 0,2, 0],
                        width: 250,
                        height: 60,
                        style: {
                                overflow: 'visible'
                        },
                        skipClone: true,
                },


                xAxis: {
                        labels: {
                                enabled: false
                        },
                        title: {
                                text: null
                        },
                        startOnTick: false,
                        endOnTick: false,
                        tickPositions: [],
			max: <?php echo $maiorNumeroSemanas; ?>
                 },

                
                yAxis: {
                        minorTickInterval: 1,
                        endOnTick: false,
                        startOnTick: false,
                        labels: {
                                enabled: false
                        },
                        title: {
                                text: null
                        },
                        tickPositions: [0],
			max: 7,
                        tickInterval: 1
                },


                title: {
                text: null
                },


                credits: {
                          enabled: false
                 },

            
                legend: {
                        enabled: false
                },


                tooltip: {
                        backgroundColor: null,
                        borderWidth: 0,
                        shadow: false,
                        useHTML: true,
                        hideDelay: 0,
                        shared: true,
                        padding: 0,
                        headerFormat: '',
                        pointFormat: <?php echo "'".get_string('numero_da_semana','block_analytics_graphs').": '"; ?> +
                                        '{point.x}<br>' +  
                                        <?php echo "'".get_string('dias_acessados','block_analytics_graphs').": '"; ?> +
                                        '{point.y}',
                        positioner: function (w, h, point) {                                return { x: point.plotX - w / 2, y: point.plotY - h};
                        }
                },


                plotOptions: {
                        series: {
                                animation: { 
                                        duration: 2000
                                },
                                lineWidth: 1,
                                shadow: false,
                                states: {
                                        hover: {
                                                lineWidth: 1
                                                }
                                        },
                                marker: {
                                        radius: 2,
                                        states: {
                                                hover: {
                                                        radius: 4                                                        }
                                                }
                                        },
                                fillOpacity: 0.25
                        },
                },


                series: [{
                    data: trata_array(aluno.acessos)
                }]

            });
        }


    function fazer_linha(array, nomes){
        $.each(nomes, function(ind,val){
            var nome = val;
            console.log(nome);
            $.each(array, function(index, value){
                        if(value){
                            if(nome === value.nome){
                                    var linha = "<tr>"+
                                            "<th><span class='nome_aluno' style='cursor:hand' id='linha-"+value.userid+"'>"+
                                                    value.nome+
                                            "</span></th>"+
                                            "<td>"+
                                                    value.pageViews+
                                            "</td>"+
                                            "<td>"+
                                                    value.totalAcessos+
                                            "</td>"+
                                            "<td id='acessos-"+value.userid+"'>"+
                                            "</td>"+
                                            "<td>"+                                                
                                            //(value.totalModulos>0? value.totalModulos : 0)+
                                            (geralNumeroDeRecursos[value.userid]? geralNumeroDeRecursos[value.userid].number : 0)+
                                            "</td>"+
                                            "<td id='modulos-"+value.userid+"'>"+
                                            "</td>"+
                                    "</tr>";
                                    $("table").append(linha);
                                    gerar_grafico(value);
                                    gerar_grafico_modulos(value);
                            }
                        }
            });
        });
    }
    
</script>


</head>
<body>
<center>
<H2><?php  echo   get_string('grafico04','block_analytics_graphs');?></H2>
<H3><?php  echo $nome_do_curso;?> </H3><H3><?php  echo   get_string('data_de_inicio','block_analytics_graphs') . ": " . userdate($startdate, get_string('strftimerecentfull'));?> </H3>
</center>
    <table id="table-sparkline" >
        <thead>
            <tr>                
		<th><?php  echo   get_string('alunos','block_analytics_graphs');?></th>                
		<th width=50><?php  echo   get_string('hits','block_analytics_graphs');?></th>                
		<th width=50><?php  echo   get_string('dias_acessados','block_analytics_graphs');?></th>                
		<th><center> <?php  echo   get_string('dias_acessados_na_semana','block_analytics_graphs'); 	
			echo "<br><i>(". get_string('numero_de_semanas','block_analytics_graphs') . ": " . ($maiorNumeroSemanas+1).")</i>";?></center></th>
		<th width=50><?php  echo   get_string('materiais_acessados','block_analytics_graphs');?></th>                
		<th><center><?php echo get_string('materiais_acessados_na_semana','block_analytics_graphs');?></center></th>
            </tr>
        </thead>
        <tbody  id='tbody-sparklines'>
            <script type="text/javascript">
                    fazer_linha(alunos, nomes);            
            </script>
        </tbody>
    </table>
    <div class="nao-acessaram">
	<br><BR>
        <center>
        <h3><?php echo get_string('legenda_nao_acessaram','block_analytics_graphs');?></h3>
        <p>


                <script type="text/javascript">
		var titulo = <?php echo json_encode(get_string('legenda_nao_acessaram','block_analytics_graphs'));?> + " - " + nome_do_curso;
		$.each(alunosSemAcesso, function(i, v) {
                                document.write(v.nome+"<br>");
		});
                var form ="<div class='div_nomes' id='alunosSemAcesso'>" +
                            gerarEmailForm(titulo , alunosSemAcesso)+
                            "</div>";
                document.write(form);
                </script>


            <input type="button" value="<?php echo get_string('enviar_email','block_analytics_graphs');?>" class="button-fancy" />
        </p>
        </center>
    </div>


<script type="text/javascript">
	var alunoComAcesso = [];
        $.each(alunos, function(ind, val){
                var div = "";
                if(val !== undefined){   
			var titulo = nome_do_curso + 
					"</h3><p style='font-size:small'>" + 
					<?php  echo json_encode(get_string('hits','block_analytics_graphs'));?> + ": "+
 					val.pageViews + 
					", "+ <?php  echo json_encode(get_string('dias_acessados','block_analytics_graphs'));?> + ": "+
					val.totalAcessos + 
					", "+ <?php  echo json_encode(get_string('materiais_acessados','block_analytics_graphs'));?> + ": "+
					val.totalRecursos ; 
			alunoComAcesso[0] = val;             
                	div = "<div class='div_nomes' id='" + val.userid + "'>" +                        
					gerarEmailForm(titulo,alunoComAcesso)
                    		"</div>";
    
                       document.write(div);     
            }
        });
        

	$(".button-fancy").bind("click", function(){                
		$(".div_nomes").dialog("close");
                $("#alunosSemAcesso").dialog("open");
        });

        $(".nome_aluno").bind("click", function(){                
		$(".div_nomes").dialog("close");
                var val = $(this).attr('id');                
		val = val.split("-");
                $("#" + val[1]).dialog("open");
        });

	enviaEmail();


	$("div .highcharts-tooltip").css('top', '-9999990px');
    </script>
</body>
</html>
