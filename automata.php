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

syslog(LOG_INFO, "PHP Automata - Executando Automata");

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
    syslog(LOG_INFO,"PHP Automata - Saindo por sessão em andamento");
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

$valor = $spreadsheet->getActiveSheet()->getCell('A1');
echo "**************************\n";
echo "Valor da celula\n";
echo $valor;
echo "\n******************************\n";

if ($valor!='Atendimento') {
    syslog(LOG_INFO, "PHP Automata - Erro na plailha recebida!");
    logout();
    return;
}

syslog(LOG_INFO,"PHP Automata - Planilha Ok");

$escreve = new \PhpOffice\PhpSpreadsheet\Writer\Csv($spreadsheet);
$escreve->setDelimiter(';');
$escreve->setEnclosure('"');
$escreve->setLineEnding("\n");
$escreve->save("./teste.csv");




$sql = "load data local infile '" . getcwd() . "/teste.csv' into table senai.tb_ofe_oferta103 character set UTF8 fields terminated by ';' optionally enclosed by '\"'  ignore 1 lines (ofe_atendimento,ofe_tipo_curso,ofe_curso,ofe_carga_horaria,ofe_area_curso,ofe_segmento_area,ofe_turma,ofe_turno,ofe_local_realizado,ofe_situacao,ofe_horario_inicio,ofe_horario_fim,@data1,@data2,ofe_dia_semana,ofe_modalidade,ofe_matriculas_estimadas,ofe_matriculas_realizadas,ofe_matriculas_evadidas,ofe_matriculas_ativas,ofe_matriculas_certificadas,ofe_docente,ofe_valor,ofe_condicoes) set ofe_data_inicio = date_add('1899-12-30',interval @data1 day), ofe_data_fim = date_add('1899-12-30',interval @data2 day),ofe_data_hora_consulta=now(),env_id=last_insert_id();";

$mysql_conn = new mysqli("localhost", "senaiuser", "M#str@d0", "senai");

$sql_ultimo_hash = "select env_hash from tb_env_envios order by env_dataenvio desc limit 1;";


if ($mysql_conn->connect_errno) {
    echo "Failed to connect to MySQL: (" . $mysql_conn->connect_errno . ") " . $mysql_conn->connect_error;
}
echo $mysql_conn->host_info . "\n";


$result_hash = $mysql_conn->query($sql_ultimo_hash);

echo "\n";
echo "Valor retornado na tabela envios";
echo $result_hash->num_rows;
echo "\n";

if($result_hash->num_rows>0) {
    $result_array = $result_hash->fetch_array();
    $hash_atual = $result_array['env_hash'];
    echo "\n";
    echo "Hash na tabela:\n";
    echo $hash_atual;
    echo "\n";
    if ($hash_atual == hash_file("sha256","./teste.csv")) {
        echo "\nArquivo sem modificação não será inserido\n";
        syslog(LOG_INFO,"PHP Automata - Sem modificação - Oferta Balcão");
    }
    else {
        $sql_insert_hash = "insert into tb_env_envios (env_hash,env_dataenvio) values ('" .
                            hash_file("sha256","./teste.csv") . "',now())";

        $mysql_conn->query($sql_insert_hash);

        if ($mysql_conn->query($sql)) {
            echo "\n";
            echo 'Valores inseridos na tabela';
            echo "\n";
        } else {
            echo $mysql_conn->error;
        }
        syslog(LOG_INFO,"PHP Automata - Tabela atualizada");
        
    }
    
    
}
$result_hash->close();

echo "\n*******************************************\n";
echo "Iniciando a consulta empresa\n";
echo "*********************************************\n";

// Iniciando a consulta empresa.....
//Acessando módulo de consulta
$dados_consulta_emp['Controle']=3;
$dados_consulta_emp['Processo']='Resultado - Oferta - Analítico';
$dados_consulta_emp['Titulo']="";
$dados_consulta_emp['Visao']=183;
$dados_consulta_emp['Xml']='<Busca><Dados Colunas="43,88,96,46,47,12,103,2,105,44" Tipo="0" Esco="103" Atend="9" PerDe="01/01/2018" PerAte="31/12/2018"></Dados></Busca>';

$resposta_consulta_emp = http("https://entrada.sesisenaisp.org.br/Consultas/,DanaInfo=sgset.sp.senai.br+Resultado.aspx",
                              "https://entrada.sesisenaisp.org.br/dana-na/auth/url_0/login.cgi",
                              "GET"
                              ,$dados_consulta_emp,
                              INCL_HEAD);


echo "***********************************" . "\n" . "Resposta consulta" . "\n" . "***********************" . "\n";
var_dump($resposta_consulta_emp);
echo "\n";
echo "\n";


$dados_export_emp['valor']=1;
$dados_export_emp['path']='testegui.xls';

$resposta_export_emp = http("https://entrada.sesisenaisp.org.br/Consultas/,DanaInfo=sgset.sp.senai.br+ExportarResultado.aspx",
                            "https://entrada.sesisenaisp.org.br/dana-na/auth/url_0/login.cgi",
                            "POST",$dados_export,EXCL_HEAD);

$hndfile_emp = fopen("./testegui.xls","wb");
fwrite($hndfile_emp,$resposta_export_emp['FILE']);
fclose($hndfile_emp);


$leitor_emp = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile("./testegui.xls");
$leitor_emp->setReadDataOnly(true);
$spreadsheet_emp = $leitor->load("./testegui.xls");





$escreve_emp = new \PhpOffice\PhpSpreadsheet\Writer\Csv($spreadsheet_emp);
$escreve_emp->setDelimiter(';');
$escreve_emp->setEnclosure('"');
$escreve_emp->setLineEnding("\n");
$escreve_emp->save("./testegui.csv");

$valor_emp = $spreadsheet_emp->getActiveSheet()->getCell('B1');
echo "**************************\n";
echo "Valor da celula empresa\n";
echo $valor_emp;
echo "\n******************************\n";

if ($valor_emp!='Situação da Proposta') {
    syslog(LOG_INFO, "PHP Automata - Erro na planilha empresa recebida!");
    logout();
    return;
}

$sql_emp = "load data local infile '/home/ubuntu/webbots/automata/testegui.csv' into table tb_cem_cursos_empresa character set UTF8 fields terminated by ';' optionally enclosed by '\"' ignore 1 lines (cem_proposta,cem_situacao,@val1,cem_tipo_cliente,cem_funcionario,cem_curso,cem_corporativo,cem_empresa,cem_cnpj,@data_prop) set cem_valor = cast(replace(replace(replace(@val1,'.','|'),',','.'),'|','') as DECIMAL(9,2)), cem_data_proposta = date_add('1899-12-30',interval @data_prop day), cem_data_cadastro=now(), en2_id = LAST_INSERT_ID();";
$sql_ultimo_hash_emp = "select en2_hash from tb_env_envios_empresa order by en2_dataenvio desc limit 1;";

$result_hash_emp = $mysql_conn->query($sql_ultimo_hash_emp);

echo "\n";
echo "Valor retornado na tabela envios";
echo $result_hash_emp->num_rows;
echo "\n";

if($result_hash_emp->num_rows>0) {
    $result_array_emp = $result_hash_emp->fetch_array();
    $hash_atual_emp = $result_array_emp['en2_hash'];
    echo "\n";
    echo "Hash na tabela:\n";
    echo $hash_atual_emp;
    echo "\n";
    if ($hash_atual_emp == hash_file("sha256","./testegui.csv")) {
        echo "\nArquivo Empresa sem modificação não será inserido\n";
        syslog(LOG_INFO,"PHP Automata - Sem modificação - Empresa");
    }
    else {
        $sql_insert_hash_emp = "insert into tb_env_envios_empresa (en2_hash,en2_dataenvio) values ('" .
                            hash_file("sha256","./testegui.csv") . "',now())";

        $mysql_conn->query($sql_insert_hash_emp);

        if ($mysql_conn->query($sql_emp)) {
            echo "\n";
            echo 'Valores inseridos na tabela empresa';
            echo "\n";
        } else {
            echo $mysql_conn->error;
        }
        syslog(LOG_INFO,"PHP Automata - Tabela empresa atualizada");
        
    }
}
else {
        $sql_insert_hash_emp = "insert into tb_env_envios_empresa (en2_hash,en2_dataenvio) values ('" .
                            hash_file("sha256","./testegui.csv") . "',now())";

        $mysql_conn->query($sql_insert_hash_emp);

        if ($mysql_conn->query($sql_emp)) {
            echo "\n";
            echo 'Valores inseridos na tabela empresa';
            echo "\n";
        } else {
            echo $mysql_conn->error;
        }
        syslog(LOG_INFO,"PHP Automata - Tabela empresa atualizada");
    
}
$result_hash_emp->close();
    


// Logout
logout();
