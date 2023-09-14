<?php

use Twilio\Rest\Content\V1\ContentInstance;
use Twilio\Rest\Messaging\V1\ServiceInstance;


require __DIR__ . '/vendor/autoload.php';


/**
 * Twilio2
 * https://www.twilio.com/docs/content
 * https://www.twilio.com/docs/content/content-api-resources
 */
class Twilio2
{
    private $sid;
    private $token;
    private $twilio;
    private $urlBase = "https://content.twilio.com/";


    public function __construct($sid, $token)
    {
        $this->sid      = $sid;
        $this->token    = $token;
        $this->twilio   = new Twilio\Rest\Client($sid, $token);
    }

    /**
     * listarPlatillaContentAndApprovals
     * https://www.twilio.com/docs/content/content-api-resources#fetch-content-and-approvals
     * @return Respuesta
     */
    function listarPlatillaContentAndApprovals(): Respuesta
    {

        $url = "{$this->urlBase}v1/Content";

        $ch = curl_init($url);

        // Configura las opciones de cURL
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Basic ' . base64_encode($this->sid . ':' . $this->token)
        ));

        // Realiza la solicitud GET
        $response   = curl_exec($ch);
        $http_code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);

        // Cierra la conexión cURL

        curl_close($ch);
        return new Respuesta($http_code, $response, $curl_error);
    }

    /**
     * listarPlatilla
     * https://www.twilio.com/docs/content/content-api-resources#fetch-mapping-between-legacy-wa-and-content-templates
     * @return ContentAndApprovalsInstance
     */
    function listarPlatilla()
    {

        $contentAndApprovals = $this->twilio->content->v1->contentAndApprovals
            ->read(20);

        // @property \DateTime|null $dateCreated
        // @property \DateTime|null $dateUpdated
        // @property string|null $sid
        // @property string|null $accountSid
        // @property string|null $friendlyName
        // @property string|null $language
        // @property array|null $variables
        // @property array|null $types
        // @property array|null $approvalRequests

        // foreach ($contentAndApprovals as $record) {
        // echo "<br>";
        // print("SID: {$record->sid}");
        // echo "<br>";
        // print("friendlyName: {$record->friendlyName}");
        // echo "<br>";
        // print("accountSid: {$record->accountSid}");
        // echo "<br>";
        // print("approvalRequests:");
        // echo  json_encode($record->approvalRequests);
        // echo "<br>";
        // print("status:");
        // echo  json_decode(json_encode($record->approvalRequests))->status;
        // print("Significado status:");
        // echo "<strong>" . Plantilla::ESTADO[json_decode(json_encode($record->approvalRequests))->status] . "</strong>";
        // echo "<br>";
        // print("rejection_reason:");
        // echo  json_decode(json_encode($record->approvalRequests))->rejection_reason;
        // echo "<br>";
        // }
        return  $contentAndApprovals;
    }

    function platillasAprobadas()
    {
        $aprobados = [];

        foreach ($this->listarPlatilla() as $record) {

            if ("approved" ==  json_decode(json_encode($record->approvalRequests))->status) {
                $aprobados[] = $record;

                // * El valor record->sid es el que se debe usar para mandar las plantillas en los whatsapp en el contentSid Ejmplo = "contentSid" => "HX9f6291...",
                print("SID: {$record->sid}");
                echo "<br>";
                print("friendlyName: {$record->friendlyName}");
                echo "<br>";
                echo "<strong>" . Plantilla::ESTADO[json_decode(json_encode($record->approvalRequests))->status] . "</strong>";
                echo "<br>";
                echo "<br>";
                echo json_encode($record->types);
                foreach ((($record->types)) as $key => $value) {
                    echo  $key;
                    echo "<br>";
                    if (key_exists("body", $value)) {

                        echo $value['body'];
                        echo "<br>";
                    }

                    if (key_exists("media", $value)) {

                        echo $value['media'][0];
                        echo "<br>";
                    }
                }
                echo "<br>";
            }
        }

        return $aprobados;
    }

    /**
     * obtener
     *
     * @param  mixed $contenteId
     * @return ContentInstance
     * https://www.twilio.com/docs/content/content-api-resources#fetch-a-content-resource
     */
    function obtener($contenteId): ContentInstance
    {

        $content = $this->twilio->content->v1->contents($contenteId)
            ->fetch();

        return $content;
    }

    /**
     * crearPlantilla
     * https://www.twilio.com/docs/content/content-api-resources#create-templates
     * @param  mixed $plantilla
     * @return Respuesta
     */
    function crearPlantilla(Plantilla $plantilla): Respuesta
    {

        $url    = "{$this->urlBase}v1/Content";
        $ch     = curl_init($url);

        // Configura las opciones de cURL
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($this->sid . ':' . $this->token)
        ));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $plantilla->toJsonApi());

        // Realiza la solicitud POST
        $response       = json_decode(curl_exec($ch));
        $http_code      = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error     = curl_error($ch);


        // Cierra la conexión cURL
        curl_close($ch);

        return new Respuesta($http_code, $response, $curl_error);
    }

    /**
     * messaging_service_sid
     * Son los Servicios que nos permiten enviar Mensajes (Sender)
     * @return ServiceInstance
     */
    function messaging_service_sid()
    {

        $services = $this->twilio->messaging->v1->services->read();

        // Itera a través de los servicios y muestra sus SIDs

        // @property string|null $sid

        // @property string|null $accountSid

        // @property string|null $friendlyName

        // @property \DateTime|null $dateCreated

        // @property \DateTime|null $dateUpdated

        // @property string|null $inboundRequestUrl

        // @property string|null $inboundMethod
        // * El  $service->sid se usa para mandar mensajes en el FROM ejemplo =  "from"       => "MG1bf093cd...",
        foreach ($services as $service) {
            echo "Service SID: " . $service->sid . "\n";
            echo "<br>";
            echo "Service friendlyName: " . $service->friendlyName . "\n";
            echo "<br>";
            echo "Service inboundRequestUrl: " . $service->inboundRequestUrl . "\n";
            echo "<br>";
            echo "Service inboundMethod: " . $service->inboundMethod . "\n";
            echo "<br>";
            echo "<br>";
        }

        return $services;
    }
}

class Respuesta
{
    public $response;
    public $status;
    public $message;

    public function __construct($status, $response, $message)
    {
        $this->response     = $response;
        $this->status       = $status;
        $this->message      = $message;
    }

    /**
     * ToArray
     *
     * @return array
     */
    public function ToArray()
    {
        return [
            'response'      => $this->response,
            'status'        => $this->status,
            'message'       => $this->message
        ];
    }

    /**
     * ToJson
     *
     * @return string
     */
    public function ToJson()
    {
        return json_encode($this->ToArray());
    }
}



class Plantilla implements Convertible
{
    private $friendly_name;
    private $language;
    private $variables = [];
    private $types = [];

    const ESTADO = [
        'unsubmitted'   => "Indica que la plantilla no se ha enviado a Twilio o WhatsApp para ningún tipo de aprobación. Estas plantillas aún se pueden usar en sesión para algunos canales y en algunas sesiones de WA sujetas a los requisitos de aprobación de WhatsApp enumerados anteriormente.",
        'received'      => "Indica que Twilio ha recibido la solicitud de aprobación de la plantilla. Todavía no está en revisión por WhatsApp.",
        'pending'       => "Indica que la plantilla está siendo revisada por WhatsApp. La revisión puede tardar hasta 24 horas.",
        'approved'      => "La plantilla fue aprobada por WhatsApp y se puede utilizar para notificar a los clientes.",
        'rejected'      => "La plantilla ha sido rechazada por WhatsApp durante el proceso de revisión.",
        'paused'        => "WhatsApp ha pausado la plantilla debido a los comentarios negativos recurrentes de los usuarios finales, que generalmente resultan de las acciones de 'bloquear' y 'reportar spam' asociadas con la plantilla. Las plantillas de mensajes con este estado no se pueden enviar a los usuarios finales.",
        'disabled'      => "WhatsApp ha desactivado la plantilla debido a comentarios negativos recurrentes de los usuarios finales o por violar una o más de las políticas de WhatsApp. Las plantillas de mensajes con este estado no se pueden enviar a los usuarios finales.",

    ];

    public function __construct($friendly_name, $language, $variables, Types $types)
    {
        $this->friendly_name = $friendly_name;
        $this->language      = $language;
        $this->variables     = $variables;
        $this->types         = $types;
    }

    function toArray()
    {
        return [
            "friendly_name" => $this->friendly_name,
            "language"      => $this->language,
            "variables"     => $this->variables,
            "types"         =>
            $this->types->toArray()

        ];
    }

    function toArrayApi()
    {
        $data = [
            "friendly_name" => $this->friendly_name,
            "language"      => $this->language,
            "variables"     => $this->variables,
            "types"         =>
            $this->types->toArrayApi()

        ];

        return array_filter($data, function ($valor) {
            return !empty($valor);
        });;
    }


    function toJson()
    {
        return json_encode($this->toArray());
    }
    function toJsonApi()
    {
        return json_encode($this->toArrayApi());
    }
}

class Types implements Convertible
{
    const TYPESPERMITDOS = [
        'twilio/text'             => "Solo Texto",
        'twilio/media'            => "Acepta Archivos",
        'twilio/location'         => "Enviar Ubicación",
        'twilio/quick-replies'    => "Respuesta rapida",
        'twilio/call-to-action'   => "Boton con acciónes",
        'twilio/list-picker'      => "Selector de item",
        'twilio/card'             => "Tarjeta con si y no",
        'whatsapp/card'           => "Tarjeta con si y no",
        'whatsapp/authentication' => "Mensaje de autenticación",
    ];

    private $type;
    private $body;
    private $actions = [];
    private $media = [];
    private $latitude;
    private $longitude;
    private $label;

    public function __construct($type, $body, $actions = [])
    {

        if (!array_key_exists($type, self::TYPESPERMITDOS)) {
            throw new InvalidArgumentException('El tipo de valor proporcionado no es válido.');
        }

        $this->type     = $type;
        $this->body     = $body;
        $this->actions  = $actions;
    }

    function twilio_text()
    {
    }
    function twilio_media()
    {
    }
    function twilio_location()
    {
    }
    function twilio_quick_replies()
    {
    }
    function twilio_call_to_action()
    {
    }
    function twilio_list_picker()
    {
    }
    function twilio_card()
    {
    }
    function whatsapp_card()
    {
    }
    function whatsapp_authentication()
    {
    }

    function toArray()
    {
        return [
            $this->type => [
                "type"      => $this->type,
                "body"      => $this->body,
                "actions"   => $this->actions
            ]
        ];
    }

    function toArrayApi()
    {
        $data = [
            $this->type => array_filter([
                "body"      => $this->body,
                "actions"   => $this->actions
            ])
        ];


        return array_filter($data, function ($valor) {
            return !empty($valor);
        });;
    }


    function toJson()
    {
        return json_encode($this->toArray());
    }

    function toJsonApi()
    {
        return json_encode($this->toArrayApi());
    }
}

interface Convertible
{
    public function toArray();
    public function toJson();
}


$sid        = "";
$token      = "";

$twilio2    = new Twilio2($sid, $token);
$services = $twilio2->messaging_service_sid();

$twilio2->platillasAprobadas();

// echo "<br>";
// echo "<br>";
// $contenteId     = "HX7...";
// echo json_encode($twilio2->obtener($contenteId)->types);
// echo "<br>";
// echo "<br>";
// $contenteId     = "HX...";
// echo json_encode($twilio2->obtener($contenteId)->types);




$client = new Twilio\Rest\Client($sid, $token);



// foreach ($services as $service) {
//     $message = $client->messages
//         ->create(
//             "whatsapp:+", // to
//             [
//                 "from"       =>  $service->sid,
//                 "contentSid" => "HX...",
//             ]
//         );
//     echo "<br>";
//     print($message->sid);
// }
echo "<br>";
$message = $client->messages
    //   ->create("whatsapp:+", // to
    ->create(
        "whatsapp:+", // to
        [
            "from"       => "MG...",
            "contentSid" => "HX..",
        ]
    );
echo "<br>";
print($message->sid);



// $plantilla = new Plantilla('Prueba 2', 'en', [], new Types('twilio/text', "Hola Mundo. Twilio2"));

// echo "<br>";
// echo $plantilla->toJsonApi();
// echo "<br>";
// print_r($plantilla->toArrayApi());
// echo "<br>";
// $respuesta = $twilio2->crearPlantilla($plantilla);
// echo $respuesta->toJson();
// echo "<br>";
// echo $twilio2->listarPlatilla()->response;
// echo "<br>";
// $twilio2->listarPlatilla();
// echo "<br>";
