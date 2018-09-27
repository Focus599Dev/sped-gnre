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
        
        $loteXmlns = $this->dom->createAttribute('xmlns');
        
        $loteXmlns->value = 'http://www.gnre.pe.gov.br';
        
        $loteGnre->appendChild($loteXmlns);
        
        $guia = $this->dom->createElement('guias');

        foreach ($this->getGuias() as $gnreGuia) {
            
            $estado = $gnreGuia->c01_UfFavorecida;

            $guiaEstado = $this->getEstadoFactory()->create($estado);

            $dados = $this->dom->createElement('TDadosGNRE');

            $this->dom->addChild($dados, 'c01_UfFavorecida', $estado, true);

            $this->dom->addChild($dados, 'c02_receita', $gnreGuia->c02_receita, false);

            $this->dom->addChild($dados, 'c25_detalhamentoReceita', $gnreGuia->c25_detalhamentoReceita, false);
            
            $this->dom->addChild($dados, 'c26_produto', $gnreGuia->c26_produto, false);
            
            $this->dom->addChild($dados, 'c27_tipoIdentificacaoEmitente', $gnreGuia->c27_tipoIdentificacaoEmitente, false);

            $c03 = $this->dom->createElement('c03_idContribuinteEmitente');

            if ($gnreGuia->c27_tipoIdentificacaoEmitente == parent::EMITENTE_PESSOA_JURIDICA) {

                $this->dom->addChild($c03, 'CNPJ', $gnreGuia->c03_idContribuinteEmitente, true);

            } else {

                $this->dom->addChild($c03, 'CPF', $gnreGuia->c03_idContribuinteEmitente, true);

            }

            $this->dom->appChild($dados, $c03, 'Falta tag "TDadosGNRE"');

            $this->dom->addChild($dados, 'c28_tipoDocOrigem', $gnreGuia->c28_tipoDocOrigem, false);

            $this->dom->addChild($dados, 'c04_docOrigem', $gnreGuia->c04_docOrigem, false);

            $this->dom->addChild($dados, 'c06_valorPrincipal', $gnreGuia->c06_valorPrincipal, false);

            $this->dom->addChild($dados, 'c10_valorTotal', $gnreGuia->c10_valorTotal, false);
            
            $this->dom->addChild($dados, 'c14_dataVencimento', $gnreGuia->c14_dataVencimento, false);

            $this->dom->addChild($dados, 'c15_convenio', $gnreGuia->c15_convenio, false);

            $this->dom->addChild($dados, 'c16_razaoSocialEmitente', $gnreGuia->c16_razaoSocialEmitente, false);

            if ($gnreGuia->c17_inscricaoEstadualEmitente) {
                
                $this->dom->addChild($dados, 'c17_inscricaoEstadualEmitente', $gnreGuia->c17_inscricaoEstadualEmitente, false);

            }
            
            $this->dom->addChild($dados, 'c18_enderecoEmitente', $gnreGuia->c18_enderecoEmitente, false);
            
            $this->dom->addChild($dados, 'c19_municipioEmitente', $gnreGuia->c19_municipioEmitente, false);

            $this->dom->addChild($dados, 'c20_ufEnderecoEmitente', $gnreGuia->c20_ufEnderecoEmitente, false);

            $this->dom->addChild($dados, 'c21_cepEmitente', $gnreGuia->c21_cepEmitente, false);

            $this->dom->addChild($dados, 'c22_telefoneEmitente', $gnreGuia->c22_telefoneEmitente, false);

            $c34_tipoIdentificacaoDestinatario = $gnreGuia->c34_tipoIdentificacaoDestinatario;

            $this->dom->addChild($dados, 'c34_tipoIdentificacaoDestinatario', $c34_tipoIdentificacaoDestinatario, false);

            $c35 = $this->dom->createElement('c35_idContribuinteDestinatario');

            $c35_idContribuinteDestinatario = $gnreGuia->c35_idContribuinteDestinatario;

            if ($gnreGuia->c34_tipoIdentificacaoDestinatario == parent::DESTINATARIO_PESSOA_JURIDICA) {

                $this->dom->addChild($c35, 'CNPJ', $c35_idContribuinteDestinatario, false);

            } else {

                $this->dom->addChild($c35, 'CPF', $c35_idContribuinteDestinatario, false);

            }

            $this->dom->appChild($dados, $c35, 'Falta tag "TDadosGNRE"');

            $c36_inscricaoEstadualDestinatario = $gnreGuia->c36_inscricaoEstadualDestinatario;

            $this->dom->addChild($dados, 'c36_inscricaoEstadualDestinatario', $c36_inscricaoEstadualDestinatario, false);

            $this->dom->addChild($dados, 'c37_razaoSocialDestinatario', $gnreGuia->c37_razaoSocialDestinatario, false);

            $this->dom->addChild($dados, 'c38_municipioDestinatario', $gnreGuia->c38_municipioDestinatario, false);

            $this->dom->addChild($dados, 'c33_dataPagamento', $gnreGuia->c33_dataPagamento, false);

            if (isset($gnreGuia->c39_camposExtras[0]['campoExtra'])){

                $c39_camposExtras = $this->dom->createElement('c39_camposExtras');
                
                $campoExtra = $this->dom->createElement('campoExtra');

                $this->dom->addChild($campoExtra, 'codigo', $gnreGuia->c39_camposExtras[0]['campoExtra']['codigo'], false);
                
                $this->dom->addChild($campoExtra, 'tipo', $gnreGuia->c39_camposExtras[0]['campoExtra']['tipo'], false);

                $this->dom->addChild($campoExtra, 'valor', $gnreGuia->c39_camposExtras[0]['campoExtra']['valor'], false);

                $this->dom->appChild($c39_camposExtras, $campoExtra, 'Falta tag "TDadosGNRE"');
                
                $this->dom->appChild($dados, $c39_camposExtras, 'Falta tag "TDadosGNRE"');
            }

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
