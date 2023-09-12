<?php

use Twilio\Rest\Content\V1\ContentInstance;

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
     * listarPlatilla
     *
     * @return Respuesta
     */
    function listarPlatilla(): Respuesta
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
        $curl_error     = curl_error($ch);

        // Cierra la conexión cURL

        curl_close($ch);
        return new Respuesta($http_code, $response, $curl_error);
    }

    /**
     * listarPlatillaAprovados
     * https://www.twilio.com/docs/content/content-api-resources#fetch-mapping-between-legacy-wa-and-content-templates
     * @return void
     */
    function listarPlatillaAprovados()
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

        foreach ($contentAndApprovals as $record) {
            print("SID: {$record->sid}");
            echo "<br>";
            print("friendlyName: {$record->friendlyName}");
            echo "<br>";
            print("accountSid: {$record->accountSid}");
            echo "<br>";
        }
        return  $contentAndApprovals;
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


// $twilio2->listarPlatillaAprovados();
echo "<br>";
$contenteId     = "";
echo $twilio2->obtener($contenteId)->sid;
// echo "<br>";
// echo $twilio2->listarPlatilla()->response;

$plantilla = new Plantilla('Prueba 2', 'en', [], new Types('twilio/text', "Hola Mundo. Twilio2"));

// echo "<br>";
// echo $plantilla->toJsonApi();
// echo "<br>";
// print_r($plantilla->toArrayApi());
// echo "<br>";
// $respuesta = $twilio2->crearPlantilla($plantilla);
// echo $respuesta->toJson();
// echo "<br>";
echo $twilio2->listarPlatilla()->response;
echo "<br>";
$twilio2->listarPlatillaAprovados();
echo "<br>";
