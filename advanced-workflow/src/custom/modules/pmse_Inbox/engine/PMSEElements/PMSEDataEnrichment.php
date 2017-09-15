<?php
use Sugarcrm\Stage2;

class PMSEDataEnrichment extends PMSEScriptTask
{
    /**
     * The Hint field name wrapper for mapping
     * @var string
     */
    protected $fieldWrapper = 'hint_%s_c';

    /**
     * List of fields to not map to a record
     * @var array
     */
    protected $skipFields = [
        'hint_account_logo_c',
        'hint_photo_c',
    ];

    /**
     * List of fields that are not mapped to special field names
     * @var array
     */
    protected $nonMappedFields = [
        'account_name' => 1,
        'title' => 1,
    ];

    /**
     * List of mapped fields from an enriched record to a bean
     * @var array
     */
    protected $customMapping = [
        'hint_account_website_c' => 'website',
    ];

    /**
     * List of URL maps
     * @var array
     */
    protected $urlMapping = [
        'hint_account_twitter_handle_c' => 'www.twitter.com/',
        'hint_account_facebook_handle_c' => 'www.facebook.com/',
        'hint_account_linkedin_handle_c' => 'www.linkedin.com/',
    ];

    /**
     * @inheritDoc
     */
    public function run($flowData, $bean = null, $externalAction = '', $arguments = array())
    {
        // Send this bean through the enrichment process
        $this->enrichBean($bean);

        // If we are waking up then update this flow, otherwise create a new one
        $flowAction = $externalAction === 'RESUME_EXECUTION' ? 'UPDATE' : 'CREATE';
        return $this->prepareResponse($flowData, 'ROUTE', $flowAction);
    }

    /**
     * Enriches a SugarBean with information from the Hint
     * enrichment service
     * @param SugarBean $bean
     */
    protected function enrichBean(SugarBean $bean = null)
    {
        // No bean, no processing
        if (empty($bean)) {
            return;
        }

        // Needed for preparing the request packet
        $isPerson = $bean instanceof Person;
        $isCompany = $bean instanceof Company;

        // Handle Person object preparation first
        if ($isPerson) {
            $data = $this->preparePersonData($bean);
        } elseif ($isCompany) {
            // Now handle Company objects
            $data = $this->prepareCompanyData($bean);
        } else {
            // This process is really only for Person and Company type modules
            return;
        }
        // Handle the enrichment
        $data = $this->enrichData($data);
        $this->applyEnrichmentToBean($bean, $data);
        $bean->save();
    }

    /**
     * Prepares a person bean for sending to the Hint service
     * @param SugarBean $bean
     * @return array
     */
    protected function preparePersonData(SugarBean $bean)
    {
        $data = [
            'full_name' => $bean->full_name,
            'first_name' => $bean->first_name,
            'last_name' => $bean->last_name,
            'email' => [
                (object) [
                    'email_address' => $bean->email[0]['email_address'],
                ],
            ]
        ];

        return $data;
    }

    /**
     * Applies enriched data to the current process bean
     * @param SugarBean $bean
     * @param array $data
     */
    protected function applyEnrichmentToBean(SugarBean $bean, array $data)
    {
        // If enrichment didn't take then we have nothing to do
        if (empty($data['enriched'])) {
            return;
        }

        // The following properties come from the enrichment service
        $beanData = $data['bean'];
        $collectedData = $data['collectedData'];

        // Handle combining the industry tags
        $cTags = ['account_tag', 'account_tag_2', 'account_tag_3', 'account_tag_4'];
        $tags = [];
        foreach ($cTags as $tag) {
            if (!empty($beanData[$tag])) {
                $tags[] = $beanData[$tag];
            }
        }
        $beanData['industry_tags'] = implode(',', $tags);

        // Handle the photo now
        if (!empty($collectedData['images'][0]['url'])) {
            $beanData['photo'] = $collectedData['images'][0]['url'];
        }

        // Create a bean data array that can be used to update necessary
        // bean fields
        $newBeanData = [];
        foreach ($beanData as $field => $value) {
            if (isset($this->nonMappedFields[$field])) {
                $newBeanData[$field] = $value;
            } else {
                $newBeanData[sprintf($this->fieldWrapper, $field)] = $value;
            }
        }

        // Loop over the bean data array and apply where necessary
        foreach ($newBeanData as $k => $v) {
            if (is_numeric($v) && empty($v)) {
                continue;
            }

            // Apply custom mappings
            if (isset($this->customMapping[$k])) {
                $k = $this->customMapping[$k];
            }

            // Apply URL mappings
            if (isset($this->urlMapping[$k])) {
                $v = $this->urlMapping[$k] . $v;
            }

            // This actually writes enriched field data to the bean
            if (isset($bean->field_defs[$k]) && empty($bean->{$k})) {
                $bean->{$k} = $v;
            }

            // This will write other bean field data from the enriched data set
            // This might not be the most accurate information and as such, is
            // not being done at this time.
            /*
            $baseKey = str_replace(explode('%s', $this->fieldWrapper), '', $k);
            if (isset($bean->field_defs[$baseKey]) && empty($bean->{$baseKey})) {
                $bean->{$baseKey} = $v;
            }
            */
        }
    }

    /**
     * Prepares a company bean for sending to the Hint service
     * @param SugarBean $bean
     * @return array
     */
    protected function prepareCompanyData(SugarBean $bean)
    {
        $data = [
            'name' => $bean->name,
            'email' => [
                (object) [
                    'email_address' => $bean->email[0]['email_address'],
                ],
            ]
        ];

        return $data;
    }

    /**
     * Sends the data enrichment request to Hint with data that has
     * been prepared for sending
     * @param array $data
     * @return array
     */
    protected function enrichData(array $data)
    {
        require_once 'custom/Stage2/Stage2Manager.php';
        $stage2 = Stage2\Stage2Manager::instance();

        // Access token for Hint
        $accessToken = $stage2->getNewAccessToken();

        // Needed properties
        $serviceUrl = $stage2->serviceUrl;
        $instanceId = $stage2->instanceId;

        $apiPath = '/v1/enrich-person-bean';

        // Hint needs this set here
        $requestData = 'bean=' . urlencode(json_encode($data));
        // Need to know REQUEST METHOD
        $method = 'GET';
        // Need to know full API path
        $apiUrl = $serviceUrl . $apiPath;
        // Need to know the data packet to send
        $apiUrl .= '?' . $requestData;
        // Need to know headers. These are important!
        $headers[] = "authToken: $accessToken";
        $headers[] = "Authorization: Basic " . base64_encode($instanceId . ':' . $stage2->licenseKey);

        $reply = $this->callService($apiUrl, $requestData, $method, $headers, ['retry' => true]);
        return empty($reply['reply']) ? [] : $reply['reply'];
    }

    /**
     * Utility method that sends an HTTP cURL request and captures the response
     * @param string $url The API url to consume
     * @param string $postBody The body of the request
     * @param string $httpAction The HTTP method
     * @param array $addedHeaders Additional headers to send
     * @param array $addedOpts Addition options for processing
     * @return array
     */
    protected function callService($url, $postBody='', $httpAction='', $addedHeaders = [], $addedOpts = [])
    {
        static $retryCount = 0;

        $retry = !empty($addedOpts['retry']);
        unset($addedOpts['retry']);

        $ch = curl_init($url);

        if (!empty($postBody)) {
            if (empty($httpAction)) {
                $httpAction = 'POST';
                curl_setopt($ch, CURLOPT_POST, 1); // This sets the POST array
                $requestMethodSet = true;
            }

            // see https://bugs.php.net/bug.php?id=69982
            $hasContentType = false;
            foreach ($addedHeaders as $header) {
                if (stripos($header, 'Content-Type') !== false) {
                    $hasContentType = true;
                    break;
                }
            }

            if (!$hasContentType) {
                $addedHeaders[] = 'Content-Type: application/json';
            }

            curl_setopt($ch, CURLOPT_POSTFIELDS, $postBody);
        } else {
            if (empty($httpAction)) {
                $httpAction = 'GET';
                $requestMethodSet = true;
            }
        }

        // Only set a custom request for not POST with a body
        // This affects the server and how it sets its superglobals
        if (empty($requestMethodSet)) {
            if ($httpAction == 'PUT' && empty($postBody) ) {
                curl_setopt($ch, CURLOPT_PUT, 1);
            } else {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $httpAction);
            }
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $addedHeaders);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_HEADER, true);


        if (is_array($addedOpts) && !empty($addedOpts)) {
            // I know curl_setopt_array() exists,
            // just wasn't sure if it was hurting stuff
            foreach ($addedOpts as $opt => $val) {
                curl_setopt($ch, $opt, $val);
            }
        }

        if ($httpAction === 'GET') {
            curl_setopt($ch, CURLOPT_HTTPGET, true);
        }

        $httpReply = curl_exec($ch);
        $httpInfo = curl_getinfo($ch);

        // Handle reauth if need be.
        if (isset($httpInfo['http_code']) && $httpInfo['http_code'] == 401) {
            return ['reply' => 'Authentication invalid'];
        }

        $httpError = $httpReply === false ? curl_error($ch) : null;

        // Handle the headers from the reply
        $headerLen = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $httpHeaders = substr($httpReply, 0, $headerLen);
        $httpHeaders = $this->parseHeaderString($httpHeaders);

        // Get just the body for parsing the reply
        $httpReply = substr($httpReply, $headerLen);

        $reply = json_decode($httpReply,true);

        if (isset($httpInfo['http_code'])) {
            if ($httpInfo['http_code'] == 200 && !$reply['enriched']) {
                if ($retryCount > 2) {
                    return ['reply' => "Request failed to get a response after $retryCount tries"];
                } else {
                    if ($retry) {
                        $addedOpts['retry'] = $retry;
                        $retryCount++;
                        return $this->callService($url, $postBody, $httpAction, $addedHeaders, $addedOpts);
                    }
                }
            }
        }

        return array('info' => $httpInfo, 'reply' => $reply, 'replyRaw' => $httpReply, 'error' => $httpError, 'headers' => $httpHeaders);
    }

    /**
     * Parses response headers from a curl request. Acts similar to get_headers()
     *
     * @param  string $header
     * @return array
     */
    protected function parseHeaderString($header)
    {
        $lines = explode("\n", rtrim($header));
        $headers = array();
        foreach ($lines as $line) {
            $parts = explode(": ", rtrim($line));
            if (count($parts) == 1) {
                $headers[] = $parts[0];
            } else {
                $headers[$parts[0]] = $parts[1];
            }
        }

        return $headers;
    }
}
