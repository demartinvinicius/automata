<?php
// Bibliotecas
include('LIB_http.php');
include('LIB_parse.php');



// Aceitar a politica de segurança da informação
$target = "https://entrada.sesisenaisp.org.br/dana-na/auth/url_0/welcome.cgi";
$method = "POST";
$ref = "https://entrada.sesisenaisp.org.br/dana-na/auth/url_0/welcome.cgi";
$data_array['sn-preauth-proceed'] = "Aceitar";

http($target,$ref,$method,$data_array,INCL_HEAD);

// Login e inicio da sessão
$target = "https://entrada.sesisenaisp.org.br/dana-na/auth/url_0/login.cgi";
$method = "POST";
$ref = "https://entrada.sesisenaisp.org.br/dana-na/auth/url_0/welcome.cgi";
$data_array_2['tz_offset']="";
$data_array_2['username']="sn73442";
$data_array_2['password']="sesisenai@18";
$data_array_2['realm']="Sesi-Senai";

http($target,$ref,$method,$data_array_2,INCL_HEAD);

// Acesso ao SGSET
$target = "https://entrada.sesisenaisp.org.br/,DanaInfo=sgset.sp.senai.br,SSO=U+";
$ref = "https://entrada.sesisenaisp.org.br/dana-na/auth/url_0/login.cgi";


$response = http_get_withheader($target,$ref);
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

$method = "POST";

$target = "https://entrada.sesisenaisp.org.br/,DanaInfo=sgset.sp.senai.br+index.aspx?Acao=Login";
$ref = "https://entrada.sesisenaisp.org.br/dana-na/auth/url_0/login.cgi";
$resposta_sgset = http($target,$ref,$method,$dados_login,INCL_HEAD);

echo "*********************" . "\n" . "Resposta sgset" . "\n" . "***********************" . "\n";
var_dump($resposta_sgset);
echo "\n";
echo "\n";

//Acessando módulo de consulta
$target = "https://entrada.sesisenaisp.org.br/Consultas/,DanaInfo=sgset.sp.senai.br+Resultado.aspx";
$ref = "https://entrada.sesisenaisp.org.br/dana-na/auth/url_0/login.cgi";
$dados_consulta['Controle']=3;
$dados_consulta['Processo']='Resultado - Oferta - Analítico';
$dados_consulta['Titulo']="";
$dados_consulta['Visao']=183;
$dados_consulta['Xml']='<Busca><Dados Colunas="5,11,12,13,71,72" Tipo="0" Esco="103" PerDe="01/01/2018" PerAte="31/12/2018"></Dados></Busca>';
$method = "GET";

$resposta_consulta = http($target,$ref,$method,$dados_consulta,INCL_HEAD);

echo "***********************************" . "\n" . "Resposta consulta" . "\n" . "***********************" . "\n";
var_dump($resposta_consulta);
echo "\n";
echo "\n";


$target = "https://entrada.sesisenaisp.org.br/Consultas/,DanaInfo=sgset.sp.senai.br+ExportarResultado.aspx";
$method = "POST";
$dados_export['valor']=1;
$dados_export['path']='teste.xls';

$resposta_export = http($target,$ref,$method,$dados_export,EXCL_HEAD);

echo "***********************************" . "\n" . "Resposta export" . "\n" . "***********************" . "\n";
var_dump($resposta_export);
echo "\n";
echo "\n";

$hndfile = fopen("./teste.xls","wb");
fwrite($hndfile,$resposta_export['FILE']);
fclose($hndfile);

// Logout
$target = "https://entrada.sesisenaisp.org.br/dana-na/auth/logout.cgi";
http_get_withheader($target,"");




?>
