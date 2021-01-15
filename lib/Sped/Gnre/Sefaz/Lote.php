<?php

/**
 * Este arquivo é parte do programa GNRE PHP
 * GNRE PHP é um software livre; você pode redistribuí-lo e/ou
 * modificá-lo dentro dos termos da Licença Pública Geral GNU como
 * publicada pela Fundação do Software Livre (FSF); na versão 2 da
 * Licença, ou (na sua opinião) qualquer versão.
 * Este programa é distribuído na esperança de que possa ser  útil,
 * mas SEM NENHUMA GARANTIA; sem uma garantia implícita de ADEQUAÇÃO a qualquer
 * MERCADO ou APLICAÇÃO EM PARTICULAR. Veja a
 * Licença Pública Geral GNU para maiores detalhes.
 * Você deve ter recebido uma cópia da Licença Pública Geral GNU
 * junto com este programa, se não, escreva para a Fundação do Software
 * Livre(FSF) Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

namespace Sped\Gnre\Sefaz;

use Sped\Gnre\Sefaz\LoteGnre;
use Sped\Gnre\Sefaz\EstadoFactory;
use NFePHP\Common\DOMImproved as Dom;
/**
 * Classe que armazena uma ou mais Guias (\Sped\Gnre\Sefaz\Guia) para serem
 * transmitidas. Não é possível transmitir uma simples guia em um formato unitário, para que seja transmitida
 * com sucesso a guia deve estar dentro de um lote (\Sped\Gnre\Sefaz\Lote).
 * @package     gnre
 * @subpackage  sefaz
 * @author      Matheus Marabesi <matheus.marabesi@gmail.com>
 * @license     http://www.gnu.org/licenses/gpl-howto.html GPL
 * @version     1.0.0
 */
class Lote extends LoteGnre
{

    /**
     * @var \Sped\Gnre\Sefaz\EstadoFactory
     */
    private $estadoFactory;

    /**
     * @var bool
     */
    private $ambienteDeTeste = false;

    /**
     * @return mixed
     */

    private $dom;

    protected $version = '2.00';

    public function __construct(){

        $this->dom = new Dom('1.0', 'UTF-8');

        $this->dom->preserveWhiteSpace = false;

        $this->dom->formatOutput = false;

    }

    public function getEstadoFactory()
    {
        if (null === $this->estadoFactory) {
            $this->estadoFactory = new EstadoFactory();
        }

        return $this->estadoFactory;
    }

    /**
     * @param mixed $estadoFactory
     * @return Lote
     */
    public function setEstadoFactory(EstadoFactory $estadoFactory)
    {
        $this->estadoFactory = $estadoFactory;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaderSoap()
    {
        $action = $this->ambienteDeTeste ?
            'http://www.testegnre.pe.gov.br/webservice/GnreRecepcaoLote' :
            'http://www.gnre.pe.gov.br/webservice/GnreRecepcaoLote';

        return array(
            'Content-Type: application/soap+xml;charset=utf-8;action="' . $action . '"',
            'SOAPAction: processar'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function soapAction()
    {
        return $this->ambienteDeTeste ?
            'https://www.testegnre.pe.gov.br/gnreWS/services/GnreLoteRecepcao' :
            'https://www.gnre.pe.gov.br/gnreWS/services/GnreLoteRecepcao';
    }

    /**
     * {@inheritdoc}
     */
    public function toXml()
    {

        $this->dom = new Dom('1.0', 'UTF-8');

        $this->dom->preserveWhiteSpace = false;

        $this->dom->formatOutput = false;

        $loteGnre = $this->dom->createElement('TLote_GNRE');

        $loteVesion = $this->dom->createAttribute('versao');

        $loteVesion->value = $this->version;

        $loteXmlns = $this->dom->createAttribute('xmlns');

        $loteXmlns->value = 'http://www.gnre.pe.gov.br';

        $loteGnre->setAttribute('versao', $this->version);

        $loteGnre->appendChild($loteXmlns);

        $guia = $this->dom->createElement('guias');

        foreach ($this->getGuias() as $gnreGuia) {

            $dados = $this->dom->createElement('TDadosGNRE');

            $dados->appendChild($loteVesion);

            $this->dom->addChild($dados, 'ufFavorecida', $gnreGuia->c01_UfFavorecida, true);

            $this->dom->addChild($dados, 'tipoGnre', $gnreGuia->tipoGnre, true);

            $contribuinteEmitente = $this->dom->createElement('contribuinteEmitente');

            $identificação = $this->dom->createElement('identificacao');

            if (strlen($gnreGuia->c03_idContribuinteEmitente) > 11) {

                $this->dom->addChild($identificação, 'CNPJ', $gnreGuia->c03_idContribuinteEmitente, true);

            } else {

                $this->dom->addChild($identificação, 'CPF', $gnreGuia->c03_idContribuinteEmitente, true);

            }

            if ($gnreGuia->c17_inscricaoEstadualEmitente) {

                $this->dom->addChild($identificação, 'IE', $gnreGuia->c17_inscricaoEstadualEmitente, false);

            }

            $contribuinteEmitente->appendChild($identificação);

            $this->dom->addChild($contribuinteEmitente, 'razaoSocial', $gnreGuia->c16_razaoSocialEmitente, false);

            $this->dom->addChild($contribuinteEmitente, 'endereco', $gnreGuia->c18_enderecoEmitente, false);

            $this->dom->addChild($contribuinteEmitente, 'municipio', $gnreGuia->c19_municipioEmitente, false);

            $this->dom->addChild($contribuinteEmitente, 'uf', $gnreGuia->c20_ufEnderecoEmitente, false);

            $this->dom->addChild($contribuinteEmitente, 'cep', $gnreGuia->c21_cepEmitente, false);

            $this->dom->addChild($contribuinteEmitente, 'telefone', $gnreGuia->c22_telefoneEmitente, false);

            $dados->appendChild($contribuinteEmitente);

            $itensGNRE = $this->dom->createElement('itensGNRE');

            $item = $this->dom->createElement('item');

            $this->dom->addChild($item, 'receita', $gnreGuia->c02_receita, false);

            $this->dom->addChild($item, 'detalhamentoReceita', $gnreGuia->c25_detalhamentoReceita, false);

           if ($gnreGuia->c04_docOrigem){
                $documentoOrigem = $this->dom->createElement('documentoOrigem', $gnreGuia->c04_docOrigem);

                $documentoTipo = $this->dom->createAttribute('tipo');

                $documentoTipo->value = $gnreGuia->c28_tipoDocOrigem;

                $documentoOrigem->appendChild($documentoTipo);

                $item->appendChild($documentoOrigem);
            }

            $this->dom->addChild($item, 'produto', $gnreGuia->c26_produto, false);

            if ($gnreGuia->periodo || $gnreGuia->mes || $gnreGuia->ano || $gnreGuia->parcela){

                $referencia =  $this->dom->createElement('referencia');

                if ($gnreGuia->periodo != ''){
                    $this->dom->addChild($referencia, 'periodo', $gnreGuia->periodo, true);
                }

                if ($gnreGuia->mes != ''){
                    $this->dom->addChild($referencia, 'mes', $gnreGuia->mes, false);
                }

                if ($gnreGuia->ano != ''){
                    $this->dom->addChild($referencia, 'ano', $gnreGuia->ano, false);
                }

                if ($gnreGuia->parcela != ''){
                    $this->dom->addChild($referencia, 'parcela', $gnreGuia->parcela, false);
                }

                $this->dom->appChild($item, $referencia, 'Falta tag "TDadosGNRE"');

            }

            $this->dom->addChild($item, 'dataVencimento', $gnreGuia->c14_dataVencimento, false);

              if (!$gnreGuia->valores){
                
                $valor = $this->dom->createElement('valor', $gnreGuia->c06_valorPrincipal);

                $valorTipo = $this->dom->createAttribute('tipo');

                $valorTipo->value = $gnreGuia->tipoValor;

                $valor->appendChild($valorTipo);

                $item->appendChild($valor);

            } else {
                
                foreach ( $gnreGuia->valores as $valores) {
                    
                    $valor = $this->dom->createElement('valor', $valores['valor']);

                    $valorTipo = $this->dom->createAttribute('tipo');
    
                    $valorTipo->value = $valores['tipo'];
    
                    $valor->appendChild($valorTipo);
    
                    $item->appendChild($valor);
                }

            }

            $this->dom->addChild($item, 'convenio', $gnreGuia->c15_convenio, false);

            $contribuinteDestinatario = $this->dom->createElement('contribuinteDestinatario');

            $identificação = $this->dom->createElement('identificacao');

            if (strlen($gnreGuia->c35_idContribuinteDestinatario) > 11) {

                $this->dom->addChild($identificação, 'CNPJ', $gnreGuia->c35_idContribuinteDestinatario, false);

            } else {

                $this->dom->addChild($identificação, 'CPF', $gnreGuia->c35_idContribuinteDestinatario, false);
            }

            $this->dom->addChild($identificação, 'IE', $gnreGuia->c36_inscricaoEstadualDestinatario, false);

            $contribuinteDestinatario->appendChild($identificação);

            $this->dom->addChild($contribuinteDestinatario, 'razaoSocial', $gnreGuia->c37_razaoSocialDestinatario, false);

            $this->dom->addChild($contribuinteDestinatario, 'municipio', $gnreGuia->c38_municipioDestinatario, false);

            $item->appendChild($contribuinteDestinatario);

            if ($gnreGuia->c39_camposExtras && is_array($gnreGuia->c39_camposExtras)){

                $c39_camposExtras = $this->dom->createElement('camposExtras');

                foreach ($gnreGuia->c39_camposExtras['campoExtra'] as $campoExtra) {

                    $campoExtraDOM = $this->dom->createElement('campoExtra');

                    $this->dom->addChild($campoExtraDOM, 'codigo', $campoExtra['codigo'], false);

                    $this->dom->addChild($campoExtraDOM, 'valor', $campoExtra['valor'], false);

                    $this->dom->appChild($c39_camposExtras, $campoExtraDOM, 'Falta tag "TDadosGNRE"');

                }

                $this->dom->appChild($item, $c39_camposExtras, 'Falta tag "TDadosGNRE"');

            }

            $itensGNRE->appendChild($item);

            $dados->appendChild($itensGNRE);

            $this->dom->addChild($dados, 'valorGNRE', $gnreGuia->c10_valorTotal, true);

            $this->dom->addChild($dados, 'dataPagamento', $gnreGuia->c33_dataPagamento, true);

            $this->dom->appChild($guia, $dados, 'Falta tag "guia"');

            $this->dom->appChild($loteGnre, $guia, 'Falta tag "guia"');
        }

        $this->dom->appendChild($loteGnre);

        return $this->dom->saveXML();
    }

    /**
     * {@inheritdoc}
     */
    public function getSoapEnvelop($gnre, $loteGnre)
    {
        $soapEnv = $gnre->createElement('soap12:Envelope');
        $soapEnv->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $soapEnv->setAttribute('xmlns:xsd', 'http://www.w3.org/2001/XMLSchema');
        $soapEnv->setAttribute('xmlns:soap12', 'http://www.w3.org/2003/05/soap-envelope');

        $gnreCabecalhoSoap = $gnre->createElement('gnreCabecMsg');
        $gnreCabecalhoSoap->setAttribute('xmlns', 'http://www.gnre.pe.gov.br/wsdl/processar');
        $gnreCabecalhoSoap->appendChild($gnre->createElement('versaoDados', '1.00'));

        $soapHeader = $gnre->createElement('soap12:Header');
        $soapHeader->appendChild($gnreCabecalhoSoap);

        $soapEnv->appendChild($soapHeader);
        $gnre->appendChild($soapEnv);

        $action = $this->ambienteDeTeste ?
            'http://www.testegnre.pe.gov.br/webservice/GnreLoteRecepcao' :
            'http://www.gnre.pe.gov.br/webservice/GnreLoteRecepcao';

        $gnreDadosMsg = $gnre->createElement('gnreDadosMsg');
        $gnreDadosMsg->setAttribute('xmlns', $action);

        $gnreDadosMsg->appendChild($loteGnre);

        $soapBody = $gnre->createElement('soap12:Body');
        $soapBody->appendChild($gnreDadosMsg);

        $soapEnv->appendChild($soapBody);
    }

    /**
     * {@inheritdoc}
     */
    public function utilizarAmbienteDeTeste($ambiente = false){
        $this->ambienteDeTeste = $ambiente;
    }

}
