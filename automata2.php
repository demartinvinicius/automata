#!/usr/bin/ php
<?php

function logout() {
    $target = "https://entrada.sesisenaisp.org.br/dana-na/auth/logout.cgi";
    http_get_withheader($target,"");    
}


// Bibliotecas
include('LIB_http.php');
include('LIB_parse.php');


// Bibliotecas Spreadsheet
require 'vendor/autoload.php';

syslog(LOG_INFO, "Executando Automata");

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

/*http("https://entrada.sesisenaisp.org.br/dana-na/auth/url_0/login.cgi",
     "https://entrada.sesisenaisp.org.br/dana-na/auth/url_0/welcome.cgi",
     "POST",$data_array_2,INCL_HEAD);*/

$response_lg = http_post_withheader("https://entrada.sesisenaisp.org.br/dana-na/auth/url_0/login.cgi",
                                 "https://entrada.sesisenaisp.org.br/dana-na/auth/url_0/welcome.cgi",
                                 $data_array_2);


if(strstr($response_lg['FILE'],"There are already other user sessions in progress")) {
    echo "\n";
    echo "Já ha uma sessão em andamento";
    echo "\n";
    syslog(LOG_INFO,"Automata - Saindo por sessão em andamento");
    return;
}


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
$dados_consulta['Xml']='<Busca><Dados Colunas="43,88,96,46,47,12,103,2,105,44" Tipo="0" Esco="103" Atend="9" PerDe="01/01/2018" PerAte="31/12/2018"></Dados></Busca>';

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
$dados_export['path']='testegui.xls';

$resposta_export = http("https://entrada.sesisenaisp.org.br/Consultas/,DanaInfo=sgset.sp.senai.br+ExportarResultado.aspx",
                        "https://entrada.sesisenaisp.org.br/dana-na/auth/url_0/login.cgi",
                        "POST",$dados_export,EXCL_HEAD);

//echo "***********************************" . "\n" . "Resposta export" . "\n" . "***********************" . "\n";
//var_dump($resposta_export);
//echo "\n";
//echo "\n";

$hndfile = fopen("./testegui.xls","wb");
fwrite($hndfile,$resposta_export['FILE']);
fclose($hndfile);

$leitor = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile("./testegui.xls");
$leitor->setReadDataOnly(true);
$spreadsheet = $leitor->load("./testegui.xls");

$escreve = new \PhpOffice\PhpSpreadsheet\Writer\Csv($spreadsheet);
$escreve->setDelimiter(';');
$escreve->setEnclosure('"');
$escreve->setLineEnding("\n");
$escreve->save("./testegui.csv");



// Logout
logout();
