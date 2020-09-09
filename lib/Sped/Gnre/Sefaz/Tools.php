<?php

namespace Sped\Gnre\Sefaz;


/**
 * Class responsible for communication with SEFAZ extends
 *
 * @category  NFePHP
 * @package   Sped\Gnre\Sefaz
 * @copyright FocusIt
 * @license   http://www.gnu.org/licenses/lgpl.txt LGPLv3+
 * @license   https://opensource.org/licenses/MIT MIT
 * @license   http://www.gnu.org/licenses/gpl.txt GPLv3+
 * @author    Marlon O. Barbosa <marlon.academi at gmail dot com>
 * @link      https://github.com/Focus599Dev/sped-gnre for the canonical source repository
 */

use Sped\Gnre\Soap\SoapCurl;
use NFePHP\Common\Soap\SoapInterface;
use NFePHP\Common\Validator;
use NFePHP\Common\Certificate;
use Sped\Gnre\Sefaz\Consulta;
use DOMDocument;

class Tools{

	/**
     * config class
     * @var \stdClass
     */
    public $config;
    /**
     * Path to storage folder
     * @var string
     */
    public $pathwsfiles = '';
    /**
     * Path to schemes folder
     * @var string
     */
    public $pathschemes = '';

    /**
     * Environment
     * @var int
     */

    public $tpAmb = 2;

    /**
     * soap class
     * @var SoapInterface
     */
    public $soap;
    /**
     * Application version
     * @var string
     */
    public $verAplic = 'Efit-2.0';
    /**
     * last soap request
     * @var string
     */
    public $lastRequest = '';
    /**
     * last soap response
     * @var string
     */
    public $lastResponse = '';
    /**
     * certificate class
     * @var Certificate
     */
    protected $certificate;
    /**
     * Sign algorithm from OPENSSL
     * @var int
     */
    protected $algorithm = OPENSSL_ALGO_SHA1;
    /**
     * @var string
     */
    protected $urlNamespace = 'http://www.gnre.pe.gov.br/webservice/GnreLoteRecepcao';

    protected $urlNamespaceConsulta = 'http://www.gnre.pe.gov.br/webservice/GnreResultadoLote';
    /**
     * @var string
     */
    protected $urlAction = [
        '1' => 'http://www.gnre.pe.gov.br/webservice/GnreRecepcaoLote',
    	'2' => 'http://www.testegnre.pe.gov.br/webservice/GnreRecepcaoLote',
    ];

    protected $urlActionConsulta = [
        '1' => 'http://www.gnre.pe.gov.br/webservice/GnreResultadoLote',
        '2' => 'http://www.testegnre.pe.gov.br/webservice/GnreResultadoLote',
    ];
    /**
     * @var \SOAPHeader
     */
    protected $objHeader;
    /**
     * @var string
     */
    protected $urlHeader = '';

    protected $urlMethod = 'GnreLoteRecepcao';
    /**
     * @var array
     */
	protected $soapnamespaces = [
        'xmlns:xsi'    => "http://www.w3.org/2001/XMLSchema-instance",
        'xmlns:xsd'    => "http://www.w3.org/2001/XMLSchema",
        'xmlns:soap' => "http://www.w3.org/2003/05/soap-envelope",
    ];

    protected $uri = [
        '1' => 'https://www.gnre.pe.gov.br/gnreWS/services/GnreLoteRecepcao',
        '2' => 'https://www.testegnre.pe.gov.br/gnreWS/services/GnreLoteRecepcao',
    ];

    protected $uriConsulta = [
        '1' => 'https://www.gnre.pe.gov.br/gnreWS/services/GnreResultadoLote',
        '2' => 'https://www.testegnre.pe.gov.br/gnreWS/services/GnreResultadoLote',
    ];

    /**
     * @var string
     */
    protected $urlVersion = '2.00';

    protected $schemaV = array(
        '1.00'=> 'V1_00',
        '2.00'=> 'V2_00',
    );

    public function __construct($configJson, Certificate $certificate)
    {
        $this->config = json_decode($configJson);

        $this->pathwsfiles = realpath(
            __DIR__ . '/../../../../storage'
        ).'/';

        $this->pathschemes = realpath(
            __DIR__ . '/../../../../schemes'
        ).'/';

        if (isset($this->schemaV[$this->urlVersion])){
            $this->pathschemes .= $this->schemaV[$this->urlVersion] . '/';
        }

        $this->certificate = $certificate;

        $this->setEnvironment($this->config->tpAmb);

        $this->soap = new SoapCurl($certificate);

        if ($this->config->certificateChain){
        	$this->soap->loadCA($this->config->certificateChain);
        }

        if ($this->config->proxy){
            $this->soap->proxy($this->config->proxy, $this->config->proxyPort, $this->config->proxyUser, $this->config->proxyPass);
        }
    }

    public function setEnvironment($tpAmb = 2){

        $this->tpAmb = $tpAmb;

    }

    public function sefazEnviaLote( $xml ){

    	$this->isValid($this->urlVersion, $xml, 'lote_gnre');

    	$xml = trim(preg_replace("/<\?xml.*?\?>/", "", $xml));

    	$body = '<gnreDadosMsg xmlns="' . $this->urlNamespace . '">' . $xml . '</gnreDadosMsg>';

    	$this->lastRequest = $body;

    	$parameters = ['gnreDadosMsg' => $body];

        $this->objHeader = $this->getSoapEnvelop('processar');

    	$this->lastResponse = $this->sendRequest($body, $parameters, $this->uri[$this->tpAmb], $this->urlMethod, $this->urlAction[$this->tpAmb]);

        return $this->lastResponse;

    }

    public function sefazConsultaRecibo( $recibo ){

        $consulta = new Consulta();

        $consulta->setRecibo($recibo);

        $consulta->setEnvironment($this->tpAmb);

        $xml = $consulta->toXml();

        $this->isValid($this->urlVersion, $xml, 'lote_gnre_consulta');

        $xml = trim(preg_replace("/<\?xml.*?\?>/", "", $xml));

        $body = '<gnreDadosMsg xmlns="' . $this->urlNamespaceConsulta . '">' . $xml . '</gnreDadosMsg>';

        $this->lastRequest = $body;

        $parameters = ['gnreDadosMsg' => $body];

        $this->objHeader = $this->getSoapEnvelop('consultar');

        $this->lastResponse = $this->sendRequest($body, $parameters, $this->uriConsulta[$this->tpAmb], 'GnreResultadoLote', $this->urlActionConsulta[$this->tpAmb]);

        return $this->lastResponse;
    }

    public function getSoapEnvelop($action){

        $header = new \stdClass();

        $header->data = array();

        $header->name = 'gnreCabecMsg';

        $header->namespace = 'http://www.gnre.pe.gov.br/wsdl/' . $action;

        $header->data['versaoDados'] = '2.00';

        return $header;
    }

    protected function isValid($version, $body, $method){
        $schema = $this->pathschemes.$method."_v$version.xsd";

        if (!is_file($schema)) {
            return true;
        }

        return Validator::isValid(
            $body,
            $schema
        );
    }

     protected function sendRequest($request, array $parameters = [], $uri, $urlMethod, $urlAction)
    {
        $this->checkSoap();

        return (string) $this->soap->send(
            $uri,
            $urlMethod,
            $urlAction,
            SOAP_1_2,
            $parameters,
            $this->soapnamespaces,
            $request,
            $this->objHeader
        );
    }

    protected function checkSoap()
    {
        if (empty($this->soap)) {
            $this->soap = new SoapCurl($this->certificate);
        }
    }

}

?>
