<?php
// Bibliotecas
include('LIB_http.php');
include('LIB_parse.php');


// Bibliotecas Spreadsheet
require 'vendor/autoload.php';



// Aceitar a politica de segurança da informação
$data_array['sn-preauth-proceed'] = "Aceitar";

http("https://entrada.sesisenaisp.org.br/dana-na/auth/url_0/welcome.cgi"
     ,"https://entrada.sesisenaisp.org.br/dana-na/auth/url_0/welcome.cgi"
     ,"POST",
     $data_array,
     INCL_HEAD);

// Login e inicio da sessão
$data_array_2['tz_offset']="";
$data_array_2['username']="sn73442";
$data_array_2['password']="sesisenai@18";
$data_array_2['realm']="Sesi-Senai";

http("https://entrada.sesisenaisp.org.br/dana-na/auth/url_0/login.cgi",
     "https://entrada.sesisenaisp.org.br/dana-na/auth/url_0/welcome.cgi",
     "POST",$data_array_2,INCL_HEAD);

// Acesso ao SGSET
$response = http_get_withheader("https://entrada.sesisenaisp.org.br/,DanaInfo=sgset.sp.senai.br,SSO=U+",
                                "https://entrada.sesisenaisp.org.br/dana-na/auth/url_0/login.cgi");

$form = return_between($response['FILE'],"<form","/form>",INCL);

echo "***************" . "\n" . "Definição do formulário" . "\n" . "*************************" . "\n";
echo $form;
echo "\n";
echo "\n";

echo "**************" . "\n" . "Parametros de validação" . "\n" . "*************************" . "\n";
// Buscar os parâmetros de validação do FORM.
$campos_ocultos = parse_array($form,'<input type="hidden"', "/>");
for($xx=0; $xx < count($campos_ocultos); $xx++) {
    $logname=get_attribute($campos_ocultos[$xx],"name");
    $logvalue=get_attribute($campos_ocultos[$xx],"value");
    $dados_login[$logname] = $logvalue;
}

$dados_login['Usr']="sn73442";
$dados_login['Pwr']="sesisenai@18";
$dados_login['hdnErro']="";
$dados_login['hdnMensagem']="";


echo "***************" . "\n" . "Dados do Post Login" . "\n" . "***************************" . "\n";
var_dump($dados_login);
echo "\n";
echo "\n";

$method_4 = "POST";

$resposta_sgset = http("https://entrada.sesisenaisp.org.br/,DanaInfo=sgset.sp.senai.br+index.aspx?Acao=Login",
                       "https://entrada.sesisenaisp.org.br/dana-na/auth/url_0/login.cgi",
                       "POST",$dados_login,INCL_HEAD);

echo "*********************" . "\n" . "Resposta sgset" . "\n" . "***********************" . "\n";
var_dump($resposta_sgset);
echo "\n";
echo "\n";

//Acessando módulo de consulta
$dados_consulta['Controle']=3;
$dados_consulta['Processo']='Resultado - Oferta - Analítico';
$dados_consulta['Titulo']="";
$dados_consulta['Visao']=183;
$dados_consulta['Xml']='<Busca><Dados Colunas="5,11,12,13,71,72,15,16,18,19,21,22,75,77,53,54,78,79,80,25,29,31" Tipo="0" Esco="103" PerDe="01/01/2018" PerAte="31/12/2018"></Dados></Busca>';

$resposta_consulta = http("https://entrada.sesisenaisp.org.br/Consultas/,DanaInfo=sgset.sp.senai.br+Resultado.aspx",
                          "https://entrada.sesisenaisp.org.br/dana-na/auth/url_0/login.cgi",
                          "GET"
                          ,$dados_consulta,
                          INCL_HEAD);

echo "***********************************" . "\n" . "Resposta consulta" . "\n" . "***********************" . "\n";
var_dump($resposta_consulta);
echo "\n";
echo "\n";


$dados_export['valor']=1;
$dados_export['path']='teste.xls';

$resposta_export = http("https://entrada.sesisenaisp.org.br/Consultas/,DanaInfo=sgset.sp.senai.br+ExportarResultado.aspx",
                        "https://entrada.sesisenaisp.org.br/dana-na/auth/url_0/login.cgi",
                        "POST",$dados_export,EXCL_HEAD);

//echo "***********************************" . "\n" . "Resposta export" . "\n" . "***********************" . "\n";
//var_dump($resposta_export);
//echo "\n";
//echo "\n";

$hndfile = fopen("./teste.xls","wb");
fwrite($hndfile,$resposta_export['FILE']);
fclose($hndfile);

$leitor = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile("./teste.xls");
$leitor->setReadDataOnly(true);
$spreadsheet = $leitor->load("./teste.xls");

$escreve = new \PhpOffice\PhpSpreadsheet\Writer\Csv($spreadsheet);
$escreve->setDelimiter(';');
$escreve->setEnclosure('"');
$escreve->setLineEnding("\n");
$escreve->save("./teste.csv");

$sql = "load data local infile '" . getcwd() . "/teste.csv' into table senai.tb_ofe_oferta103 character set UTF8 fields terminated by ';' optionally enclosed by '\"'  ignore 1 lines (ofe_atendimento,ofe_tipo_curso,ofe_curso,ofe_carga_horaria,ofe_area_curso,ofe_segmento_area,ofe_turma,ofe_turno,ofe_local_realizado,ofe_situacao,ofe_horario_inicio,ofe_horario_fim,@data1,@data2,ofe_dia_semana,ofe_modalidade,ofe_matriculas_estimadas,ofe_matriculas_realizadas,ofe_matriculas_evadidas,ofe_matriculas_ativas,ofe_matriculas_certificadas,ofe_docente,ofe_valor,ofe_condicoes) set ofe_data_inicio = date_add('1899-12-30',interval @data1 day), ofe_data_fim = date_add('1899-12-30',interval @data2 day),ofe_data_hora_consulta=now();";

$mysql_conn = new mysqli("localhost", "senaiuser", "M#str@d0", "senai");
if ($mysql_conn->connect_errno) {
    echo "Failed to connect to MySQL: (" . $mysql_conn->connect_errno . ") " . $mysql_conn->connect_error;
}
echo $mysql_conn->host_info . "\n";

if ($mysql_conn->query($sql)) {
    echo "\n";
    echo 'Valores inseridos na tabela';
    echo "\n";
} else {
    echo $mysql_conn->error;
}

// Logout
$target = "https://entrada.sesisenaisp.org.br/dana-na/auth/logout.cgi";
http_get_withheader($target,"");
