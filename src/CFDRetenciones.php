<?php
namespace AlfredoMejia3001\CFDIRetenciones;



class CFDRetenciones {
    private $cRetenciones;
    private $ccfdiRelacionados;
    private $cemisor;
    private $creceptor;
    private $cperiodo;
    private $ctotales;
    private $creceptorNacional;
    private $creceptorExtranjero;
    private $ctotalesImpRetenidos = array();

    public function __construct() {
        $this->cRetenciones = new stdClass();
        $this->ccfdiRelacionados = new stdClass();
        $this->cemisor = new stdClass();
        $this->creceptor = new stdClass();
        $this->cperiodo = new stdClass();
        $this->ctotales = new stdClass();
        $this->creceptorNacional = new stdClass();
        $this->creceptorExtranjero = new stdClass();
    }

    public function Schemma() {
        return array(
            "RetencionesV20" => "http://www.sat.gob.mx/esquemas/retencionpago/2 http://www.sat.gob.mx/esquemas/retencionpago/2/retencionpagov2.xsd"
        );
    }

    public function Retenciones($FechaExp, $LugarExpRetenc, $CveRetenc, $FolioInt = null, $DescRetenc = null) {
        $this->cRetenciones->Version = "2.0";
        $this->cRetenciones->SchemaLocalization = $this->Schemma()["RetencionesV20"];
        $this->cRetenciones->FolioInt = $FolioInt;
        $this->cRetenciones->Sello = "";
        $this->cRetenciones->NoCertificado = "";
        $this->cRetenciones->Certificado = "";
        $this->cRetenciones->FechaExp = $FechaExp;
        $this->cRetenciones->LugarExpRetenc = $LugarExpRetenc;
        $this->cRetenciones->CveRetenc = $CveRetenc;
        $this->cRetenciones->DescRetenc = $DescRetenc;
    }

    public function AgregarCfdiRetenRelacionados($TipoRelacion, $UUID) {
        $this->ccfdiRelacionados->TipoRelacion = $TipoRelacion;
        $this->ccfdiRelacionados->UUID = $UUID;
    }

    public function AgregarEmisor($RfcE, $NomDenRazSocE, $RegimenFiscalE) {
        $this->cemisor->RfcE = $RfcE;
        $this->cemisor->NomDenRazSocE = $NomDenRazSocE;
        $this->cemisor->RegimenFiscalE = $RegimenFiscalE;
    }

    public function AgregarReceptorNacional($RfcR, $NomDenRazSocR, $DomicilioFiscalR, $CurpR = null) {
        $this->creceptorNacional->RfcR = $RfcR;
        $this->creceptorNacional->NomDenRazSocR = $NomDenRazSocR;
        $this->creceptorNacional->DomicilioFiscalR = $DomicilioFiscalR;
        $this->creceptorNacional->CurpR = $CurpR;
    }

    public function AgregarReceptorExtranjero($NomDenRazSocR, $NumRegIdTribR = null) {
        $this->creceptorExtranjero->NomDenRazSocR = $NomDenRazSocR;
        $this->creceptorExtranjero->NumRegIdTribR = $NumRegIdTribR;
    }

    public function AgregarReceptor($NacionalidadR) {
        $this->creceptor->NacionalidadR = $NacionalidadR;
        if ($NacionalidadR == "Nacional" && isset($this->creceptorNacional->RfcR)) {
            $this->creceptor->Item = $this->creceptorNacional;
        } elseif ($NacionalidadR == "Extranjero" && isset($this->creceptorExtranjero->NomDenRazSocR)) {
            $this->creceptor->Item = $this->creceptorExtranjero;
        }
    }

    public function AgregarPeriodo($MesIni, $MesFin, $Ejercicio) {
        $this->cperiodo->MesIni = $MesIni;
        $this->cperiodo->MesFin = $MesFin;
        $this->cperiodo->Ejercicio = $Ejercicio;
    }

    public function AgregarTotalesImpRetenidos($MontoRet, $TipoPagoRet, $BaseRet = null, $ImpuestoRet = null) {
        $impRetenido = new stdClass();
        $impRetenido->BaseRet = $BaseRet;
        $impRetenido->ImpuestoRet = $ImpuestoRet;
        $impRetenido->MontoRet = $MontoRet;
        $impRetenido->TipoPagoRet = $TipoPagoRet;
        
        array_push($this->ctotalesImpRetenidos, $impRetenido);
    }

    public function AgregarTotales($MontoTotOperacion, $MontoTotGrav, $MontoTotExent, $MontoTotRet, $UtilidadBimestral = null, $ISRCorrespondiente = null) {
        $this->ctotales->MontoTotOperacion = $MontoTotOperacion;
        $this->ctotales->MontoTotGrav = $MontoTotGrav;
        $this->ctotales->MontoTotExent = $MontoTotExent;
        $this->ctotales->MontoTotRet = $MontoTotRet;
        $this->ctotales->UtilidadBimestral = $UtilidadBimestral;
        $this->ctotales->ISRCorrespondiente = $ISRCorrespondiente;
        $this->ctotales->ImpRetenidos = !empty($this->ctotalesImpRetenidos) ? $this->ctotalesImpRetenidos : null;
    }

    private function CrearXML() {
        $retenciones = $this->cRetenciones;
        $retenciones->Emisor = $this->cemisor;
        $retenciones->Receptor = $this->creceptor;
        $retenciones->Periodo = $this->cperiodo;
        $retenciones->Totales = $this->ctotales;
        
        return $retenciones;
    }

    public function CrearFacturaXML($FinkokUser, $FinkokPass, $KeyFile, $Pass, $CerFile, &$Errores, $Ruta = null, $nameXML = null, $RutaCadena = "", &$ErrorE = null) {
        $c = $this->CrearXML();
        $RFC = $this->cemisor->RfcE;

        // Validar RFCs de prueba
        $RFCsPrueba = array("EKU9003173C9", "IIA040805DZ4", "IVD920810GU2", "MISC491214B86", "XIQB891116QE4");
        
        if (!in_array($RFC, $RFCsPrueba)) {
            $response30 = $this->GetMethod($FinkokUser, $FinkokPass, $RFC);
            if ($response30 != "A") {
                $Errores = $response30;
                return false;
            } elseif ($response30 == "I") {
                $Errores = "Error: El RFC emisor se encuentra Inactivo en la cuenta";
                return false;
            }
        }

        try {
            // Configuración de rutas
            $nombreXML = $nameXML ? strtoupper(str_replace(".XML", "", $nameXML)) : "CFDI";
            $rutaFinal = $Ruta ? rtrim($Ruta, '/') . '/' : "C:/Users/" . get_current_user() . "/Documents/" . $RFC . "/";
            
            if (!file_exists($rutaFinal)) {
                mkdir($rutaFinal, 0777, true);
            }
            
            $xmlruta = $rutaFinal . $nombreXML . ".xml";

            // Limpiar archivo existente
            if (file_exists($xmlruta)) {
                unlink($xmlruta);
            }

            // Crear estructura XML
            $xml = new DOMDocument('1.0', 'UTF-8');
            $xml->formatOutput = true;
            $xml->preserveWhiteSpace = false;
            
            // Elemento raíz
            $root = $xml->createElementNS("http://www.sat.gob.mx/esquemas/retencionpago/2", "retenciones:Retenciones");
            $root->setAttribute("xmlns:xsi", "http://www.w3.org/2001/XMLSchema-instance");
            $root->setAttribute("xsi:schemaLocation", $this->Schemma()["RetencionesV20"]);
            $root->setAttribute("Version", "2.0");
            $root->setAttribute("FolioInt", $this->cRetenciones->FolioInt ?? '');
            $root->setAttribute("Sello", "");
            $root->setAttribute("NoCertificado", "");
            $root->setAttribute("Certificado", "");
            $root->setAttribute("FechaExp", $this->cRetenciones->FechaExp);
            $root->setAttribute("LugarExpRetenc", $this->cRetenciones->LugarExpRetenc);
            $root->setAttribute("CveRetenc", $this->cRetenciones->CveRetenc);
            $root->setAttribute("DescRetenc", $this->cRetenciones->DescRetenc ?? '');
            $xml->appendChild($root);

            // CFDI Relacionados
            if (isset($this->ccfdiRelacionados->UUID)) {
                $cfdiRelacionados = $xml->createElement("retenciones:CfdiRetenRelacionados");
                $cfdiRelacionados->setAttribute("TipoRelacion", $this->ccfdiRelacionados->TipoRelacion);
                $cfdiRelacionados->setAttribute("UUID", $this->ccfdiRelacionados->UUID);
                $root->appendChild($cfdiRelacionados);
            }

            // Emisor
            $emisor = $xml->createElement("retenciones:Emisor");
            $emisor->setAttribute("RfcE", $this->cemisor->RfcE);
            $emisor->setAttribute("NomDenRazSocE", $this->cemisor->NomDenRazSocE);
            $emisor->setAttribute("RegimenFiscalE", $this->cemisor->RegimenFiscalE);
            $root->appendChild($emisor);

            // Receptor
            $receptor = $xml->createElement("retenciones:Receptor");
            $receptor->setAttribute("NacionalidadR", $this->creceptor->NacionalidadR);
            
            if ($this->creceptor->NacionalidadR == "Nacional") {
                $receptorNacional = $xml->createElement("retenciones:Nacional");
                $receptorNacional->setAttribute("RfcR", $this->creceptorNacional->RfcR);
                $receptorNacional->setAttribute("NomDenRazSocR", $this->creceptorNacional->NomDenRazSocR);
                $receptorNacional->setAttribute("DomicilioFiscalR", $this->creceptorNacional->DomicilioFiscalR);
                if (isset($this->creceptorNacional->CurpR)) {
                    $receptorNacional->setAttribute("CurpR", $this->creceptorNacional->CurpR);
                }
                $receptor->appendChild($receptorNacional);
            } else {
                $receptorExtranjero = $xml->createElement("retenciones:Extranjero");
                $receptorExtranjero->setAttribute("NomDenRazSocR", $this->creceptorExtranjero->NomDenRazSocR);
                if (isset($this->creceptorExtranjero->NumRegIdTribR)) {
                    $receptorExtranjero->setAttribute("NumRegIdTribR", $this->creceptorExtranjero->NumRegIdTribR);
                }
                $receptor->appendChild($receptorExtranjero);
            }
            $root->appendChild($receptor);

            // Periodo
            $periodo = $xml->createElement("retenciones:Periodo");
            $periodo->setAttribute("MesIni", $this->cperiodo->MesIni);
            $periodo->setAttribute("MesFin", $this->cperiodo->MesFin);
            $periodo->setAttribute("Ejercicio", $this->cperiodo->Ejercicio);
            $root->appendChild($periodo);

            // Totales e Impuestos Retenidos
            $totales = $xml->createElement("retenciones:Totales");
            $totales->setAttribute("MontoTotOperacion", $this->ctotales->MontoTotOperacion);
            $totales->setAttribute("MontoTotGrav", $this->ctotales->MontoTotGrav);
            $totales->setAttribute("MontoTotExent", $this->ctotales->MontoTotExent);
            $totales->setAttribute("MontoTotRet", $this->ctotales->MontoTotRet);
            
            if (isset($this->ctotales->UtilidadBimestral)) {
                $totales->setAttribute("UtilidadBimestral", $this->ctotales->UtilidadBimestral);
            }
            
            if (isset($this->ctotales->ISRCorrespondiente)) {
                $totales->setAttribute("ISRCorrespondiente", $this->ctotales->ISRCorrespondiente);
            }

            if (!empty($this->ctotalesImpRetenidos)) {
                foreach ($this->ctotalesImpRetenidos as $impRetenido) {
                    $impuesto = $xml->createElement("retenciones:ImpRetenidos");
                    if (isset($impRetenido->BaseRet)) {
                        $impuesto->setAttribute("BaseRet", $impRetenido->BaseRet);
                    }
                    if (isset($impRetenido->ImpuestoRet)) {
                        $impuesto->setAttribute("ImpuestoRet", $impRetenido->ImpuestoRet);
                    }
                    $impuesto->setAttribute("MontoRet", $impRetenido->MontoRet);
                    $impuesto->setAttribute("TipoPagoRet", $impRetenido->TipoPagoRet);
                    $totales->appendChild($impuesto);
                }
            }
            $root->appendChild($totales);

            // Guardar XML temporal para certificación
            $tempFile = tempnam(sys_get_temp_dir(), 'reten_');
            $xml->save($tempFile);

            // Agregar certificado y número de certificado
            $this->AgregaCertificado($CerFile, $xml);
            $this->AgregaNoCertificado($CerFile, $xml);

            // Generar cadena original
            $RutaCadena = $RutaCadena ?: dirname(__FILE__) . "/XSLT/retenciones20.xslt";
            if (!file_exists($RutaCadena)) {
                throw new Exception("Archivo XSLT no encontrado en: " . $RutaCadena);
            }
            
            $Cadena = $this->GetCadenaOriginal($xml, $RutaCadena, $rutaFinal, $nombreXML);
            
            // Generar y agregar sello
            $this->SellarXML($KeyFile, $Pass, $CerFile, $xml, $Cadena);
            
            // Guardar XML final
            $xml->save($xmlruta);
            
            // Limpiar nodos vacíos
            $this->RemoveEmptyNodes($xml);
            $xml->save($xmlruta);
            
            // Eliminar archivo temporal
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }

            return true;
        } catch (Exception $ex) {
            if (isset($tempFile) && file_exists($tempFile)) {
                @unlink($tempFile);
            }
            $ErrorE = $ex;
            $Errores = $ex->getMessage();
            return false;
        }
    }

    private function GetMethod($username, $password, $taxpayer_id) {
        try {
            $url = "https://facturacion.finkok.com/servicios/soap/registration.wsdl";
            
            $soapRequest = $this->CreateSoapEnvelope($username, $password, $taxpayer_id);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml;charset=utf-8'));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $soapRequest);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            
            if (curl_errno($ch)) {
                return curl_error($ch);
            }
            
            curl_close($ch);
            
            $xml = new DOMDocument();
            $xml->loadXML($response);
            
            // Buscar mensaje de error
            $message = $xml->getElementsByTagNameNS('apps.services.soap.core.views', 'message');
            if ($message->length > 0) {
                return $message->item(0)->nodeValue;
            }
            
            // Buscar status
            $status = $xml->getElementsByTagNameNS('apps.services.soap.core.views', 'status');
            if ($status->length > 0) {
                return $status->item(0)->nodeValue;
            }
            
            return "No se obtuvo respuesta válida.";
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }

    private function CreateSoapEnvelope($userName, $password, $taxpayer_id) {
        return '<?xml version="1.0" encoding="utf-8"?>
           <soapenv:Envelope xmlns:reg="http://facturacion.finkok.com/registration" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
              <soapenv:Header/>
              <soapenv:Body>
                  <reg:get>
                      <reg:reseller_username>' . $userName . '</reg:reseller_username>
                      <reg:reseller_password>' . $password . '</reg:reseller_password>
                      <reg:taxpayer_id>' . $taxpayer_id . '</reg:taxpayer_id>
                  </reg:get>
              </soapenv:Body>
           </soapenv:Envelope>';
    }

    private function RemoveEmptyNodes(DOMDocument &$dom) {
        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query('//*[not(node())]');
        
        foreach ($nodes as $node) {
            if ($node->attributes->length == 0) {
                $node->parentNode->removeChild($node);
            }
        }
    }

    private function GetCadenaOriginal(DOMDocument $xml, $fileXSLT, $ruta, $nameXML) {
        $xsl = new DOMDocument();
        if (!$xsl->load($fileXSLT)) {
            throw new Exception("Error al cargar el archivo XSLT");
        }
        
        $proc = new XSLTProcessor();
        $proc->importStyleSheet($xsl);
        
        $cadenaOriginal = $proc->transformToXML($xml);
        if ($cadenaOriginal === false) {
            throw new Exception("Error al generar cadena original");
        }
        
        if ($ruta) {
            file_put_contents($ruta . "cadenaPrueba_" . $nameXML . ".txt", $cadenaOriginal);
        }
        
        return $cadenaOriginal;
    }

    private function SellarXML($KeyFile, $Pass, $CerFile, DOMDocument &$xml, $Cadena) {
        if (!file_exists($KeyFile)) {
            throw new Exception("Archivo de llave privada no encontrado: " . $KeyFile);
        }
        
        $keyContent = file_get_contents($KeyFile);
        if ($keyContent === false) {
            throw new Exception("No se pudo leer la llave privada");
        }
        
        // Verificar si la llave necesita conversión a PEM
        if (strpos($keyContent, '-----BEGIN') === false) {
            throw new Exception("La llave privada debe estar en formato PEM");
        }
        
        $privateKey = openssl_pkey_get_private($keyContent, $Pass);
        if ($privateKey === false) {
            throw new Exception("Error al cargar llave privada: " . openssl_error_string());
        }
        
        // Generar firma
        $signature = '';
        if (!openssl_sign($Cadena, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new Exception("Error al generar firma: " . openssl_error_string());
        }
        
        // Codificar en base64 y agregar al XML
        $sello = base64_encode($signature);
        $xml->documentElement->setAttribute("Sello", $sello);
        
        // Liberar recursos
        openssl_free_key($privateKey);
    }

    private function AgregaCertificado($CerFile, DOMDocument &$xml) {
        if (!file_exists($CerFile)) {
            throw new Exception("Archivo de certificado no encontrado: " . $CerFile);
        }
        
        $certContent = file_get_contents($CerFile);
        if ($certContent === false) {
            throw new Exception("No se pudo leer el contenido del certificado");
        }
        
        // Convertir a formato PEM si es necesario
        if (strpos($certContent, '-----BEGIN CERTIFICATE-----') === false) {
            $certContent = "-----BEGIN CERTIFICATE-----\n" . 
                          chunk_split(base64_encode($certContent), 64) . 
                          "-----END CERTIFICATE-----";
        }
        
        $certBase64 = base64_encode($certContent);
        $root = $xml->documentElement;
        $root->setAttribute("Certificado", $certBase64);
    }

    private function AgregaNoCertificado($CerFile, DOMDocument &$xml) {
        if (!file_exists($CerFile)) {
            throw new Exception("Archivo de certificado no encontrado: " . $CerFile);
        }
        
        $certContent = file_get_contents($CerFile);
        if ($certContent === false) {
            throw new Exception("No se pudo leer el certificado");
        }
        
        $certData = openssl_x509_parse($certContent);
        if ($certData === false) {
            throw new Exception("No se pudo parsear el certificado");
        }
        
        // Formatear correctamente el número de serie (eliminar caracteres no numéricos)
        $serialNumber = $certData['serialNumber'];
        $noCertificado = preg_replace('/[^0-9]/', '', $serialNumber);
        
        $xml->documentElement->setAttribute("NoCertificado", $noCertificado);
    }
}