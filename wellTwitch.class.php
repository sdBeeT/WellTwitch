<?php
/**
 * WellTwitch is a PHP Twitch API class.
 *
 * @author   Aleksandr Burov <sdbeet@yandex.ua>
 * @access   public
 * @see      http://www.welltwitch.info
 *
 */
class WellTwitch
{
    private $api = array
    (
        'kraken'            => 'https://api.twitch.tv/kraken/',
        'oauth2_authorize'  => 'https://api.twitch.tv/kraken/oauth2/authorize',
        'oauth2_token'      => 'https://api.twitch.tv/kraken/oauth2/token'
    );



/* START --> OAUTH2 PARAMETERS */

    private $oauth2 = true;

    protected $client_id     = '3dcnkzovtfbmdfzeayuqnb1z282sm0g';
    protected $client_secret = '3e7ct9voxswa3ntih59viqku4ni7qaf';
    protected $redirect_url  = 'http://welltwitch.info';

    protected $state = true;
    protected $state_value   = 'your_state_pass';

    protected $response_type = 'code';
    protected $grant_type    = 'authorization_code';

    protected $scopes = array
    (
        'chat_login',

        'user_read',
        'user_blocks_edit',
        'user_blocks_read',
        'user_follows_edit',
        'user_subscriptions',

        'channel_read',
        'channel_editor',
        'channel_commercial',
        'channel_stream',
        'channel_subscriptions',
        'channel_feed_read',
        'channel_feed_edit',
        'channel_check_subscription'
    );

/* END --> OAUTH2 PARAMETERS */


    /**
     * WellTwitch constructor.
     */
    function __construct()
    {
        if ($this->oauth2)
        {
            ob_start();

            if (isset($_GET['code']))
                $this->createOAuth2Token();

            if (isset($_GET['error']))
                header('Location: ' . $this->redirect_url);

        }
    }


/* START --> OAuth2 --> PUBLIC METHODS */

    /**
     * @return string
     */
    public function getTwitchAuthUrl()
    {
        $url = $this->api['oauth2_authorize'].
            '?response_type='.$this->response_type.
            '&client_id='.$this->client_id.
            '&redirect_uri='.$this->redirect_url.
            '&scope='.$this->scopes();

        if ($this->state) {
            $url .= '&state='.$this->state_value;
        }

        return $url;
    }

    /**
     * @return mixed
     */
    public function getTwitchToken()
    {
        if ($this->isTwitchTokenExist()) {
            return $this->decodeTwitchToken($_COOKIE['wt_token']);
        }
        else {
            return false;
        }
    }

    /**
     * @return array
     */
    public function getOAuth2AuthUserFull()
    {
        return $this->curlAuthRequest();
    }

    /**
     * @return array
     */
    public function getOAuth2AuthUserLinks()
    {
        return $this->curlAuthRequest()['_links'];
    }

    /**
     * @return string
     */
    public function getOAuth2AuthUserName()
    {
        return $this->curlAuthRequest()['token']['user_name'];
    }

    /**
     * @return string
     */
    public function getOAuth2AuthUserClientId()
    {
        return $this->curlAuthRequest()['token']['client_id'];
    }

    /**
     * @return array
     */
    public function getOAuth2AuthUserScopes()
    {
        return $this->curlAuthRequest()['token']['authorization']['scopes'];
    }

    /**
     * @return string
     */
    public function getOAuth2AuthUserCreateAt()
    {
        return $this->curlAuthRequest()['token']['authorization']['created_at'];
    }

    /**
     * @return string
     */
    public function getOAuth2AuthUserUpdateAt()
    {
        return $this->curlAuthRequest()['token']['authorization']['updated_at'];
    }

    public function delTwitchTokenWhenUserWant()
    {
        if (isset($_GET['x']) == 1)
        {
            setCookie('wt_token', '');

            header("Location: $this->redirect_url");
        }

    }
/* END --> OAuth2 --> PUBLIC METHODS */

/* START --> OAuth2 --> PROTECTED METHODS */


    /**
     * @param $code
     * @return string
     */
    protected function OAuth2Token($code)
    {
        if ($this->state) $state = $this->state_value;



        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->api['oauth2_token']);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, "client_id=$this->client_id&client_secret=$this->client_secret&grant_type=$this->grant_type&redirect_uri=$this->redirect_url&scope={$this->scopes()}&code=$code&state=$state");
        $out = curl_exec($curl);

        curl_close($curl);

        $json_decode = json_decode($out, true);

        if(isset($json_decode['error'])) {
            exit('OAuth2Token() - Error: '. $json_decode['error'] .', Status: '. $json_decode['status']
                . ', Message: ' . $json_decode['message']);
        }

        return $json_decode['access_token'];
    }

    protected function doRequest($url, $json = false)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $out = curl_exec($curl);
        curl_close($curl);

        if ($json === true)
        {
            return $out;
        }
        else
        {
            $array = json_decode($out, true);
            return $array;
        }

    }

    /**
     * @return array
     *
     * OAuth2 Token
     * https://api.twitch.tv/kraken?oauth_token=[TOKEN]
     */
    protected function curlAuthRequest()
    {
        if ($this->isTwitchTokenExist() == false) return false;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->api['kraken'] . '?oauth_token=' . $this->getTwitchToken());
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $out = curl_exec($curl);
        curl_close($curl);

        $json_decode_array = json_decode($out, true);

        if (!$json_decode_array['identified']) exit('Token Not Identified! Twitch API TOKEN BAD');

        return $json_decode_array;
    }

    /**
     * @return bool
     */
    public function isTwitchTokenExist()
    {
        if (isset($_COOKIE['wt_token'])) {
            return true;
        }
        else {
            return false;
        }
    }

    /**
     * @return bool
     */
    private function isStateOk()
    {
        if ($this->state_value == $_GET['state']) {
            return true;
        }
        else {
            return false;
        }
    }

    /**
     * @return string
     */
    private function scopes()
    {
        return implode(" ", $this->scopes);
    }

    private function createOAuth2Token()
    {
        if ($this->state)
            if (!$this->isStateOk()) exit('Wrong STATE!');

        $token = $this->OAuth2Token($_GET['code']);
        setcookie('wt_token', $this->encodeTwitchToken($token), time() + 157680000);

        // Redirect
        header('Location: ' . $this->redirect_url);
    }

    private function encodeTwitchToken($token)
    {
        return base64_encode($token);
    }

    private function decodeTwitchToken($token)
    {
        return base64_decode($token);
    }


/* END --> OAuth2 --> PROTECTED METHODS */


    /* --- OAuth2 --- FINISH --- */


        /**
     * @param string $channel
     * @return array
     */
    public function getChannelFull($channel = 'test_user1', $json = false)
    {
        if ($json)
        {
            return $this->doRequest($this->api['kraken'] . 'channels/' . $channel, $json);
        }
        else
        {
            return $this->doRequest($this->api['kraken'] . 'channels/' . $channel);
        }
    }

    /**
     * @param string $channel
     * @return string
     */
    public function getChannelStatus($channel = 'test_user1')
    {
        return $this->doRequest($this->api['kraken'] . 'channels/' . $channel)['status'];
    }

    /**
     * @param string $channel
     * @return string
     */
    public function getChannelBroadcastLang($channel = 'test_user1')
    {
        return $this->doRequest($this->api['kraken'] . 'channels/' . $channel)['broadcaster_language'];
    }

    /**
     * @param string $channel
     * @return string
     */
    public function getChannelDisplayName($channel = 'test_user1')
    {
        return $this->doRequest($this->api['kraken'] . 'channels/' . $channel)['display_name'];
    }

    /**
     * @param string $channel
     * @return string
     */
    public function getChannelGame($channel = 'test_user1')
    {
        return $this->doRequest($this->api['kraken'] . 'channels/' . $channel)['game'];
    }

    /**
     * @param string $channel
     * @return string
     */
    public function getChannelLang($channel = 'test_user1')
    {
        return $this->doRequest($this->api['kraken'] . 'channels/' . $channel)['language'];
    }

    /**
     * @param string $channel
     * @return int
     */
    public function getChannelID($channel = 'test_user1')
    {
        return $this->doRequest($this->api['kraken'] . 'channels/' . $channel)['_id'];
    }

    /**
     * @param string $channel
     * @return string
     */
    public function getChannelName($channel = 'test_user1')
    {
        return $this->doRequest($this->api['kraken'] . 'channels/' . $channel)['name'];
    }

    /**
     * @param string $channel
     * @return string
     */
    public function getChannelCreatedAt($channel = 'test_user1')
    {
        return $this->doRequest($this->api['kraken'] . 'channels/' . $channel)['created_at'];
    }

    /**
     * @param string $channel
     * @return string
     */
    public function getChannelUpdatedAt($channel = 'test_user1')
    {
        return $this->doRequest($this->api['kraken'] . 'channels/' . $channel)['updated_at'];
    }

    /**
     * @param string $channel
     * @return string
     */
    public function getChannelLogo($channel = 'test_user1')
    {
        return $this->doRequest($this->api['kraken'] . 'channels/' . $channel)['logo'];
    }

    /**
     * @param string $channel
     * @return string
     */
    public function getChannelVideoBanner($channel = 'test_user1')
    {
        return $this->doRequest($this->api['kraken'] . 'channels/' . $channel)['video_banner'];
    }

    /**
     * @param string $channel
     * @return int
     */
    public function getChannelDelay($channel = 'test_user1')
    {
        return $this->doRequest($this->api['kraken'] . 'channels/' . $channel)['delay'];
    }

    /**
     * @param string $channel
     * @return string
     */
    public function getChannelBanner($channel = 'test_user1')
    {
        return $this->doRequest($this->api['kraken'] . 'channels/' . $channel)['banner'];
    }

    /**
     * @param string $channel
     * @return string
     */
    public function getChannelBackground($channel = 'test_user1')
    {
        return $this->doRequest($this->api['kraken'] . 'channels/' . $channel)['background'];
    }

    /**
     * @param string $channel
     * @return string
     */
    public function getChannelProfileBanner($channel = 'test_user1')
    {
        return $this->doRequest($this->api['kraken'] . 'channels/' . $channel)['profile_banner'];
    }

    /**
     * @param string $channel
     * @return string
     */
    public function getChannelProfileBannerBgColor($channel = 'test_user1')
    {
        return $this->doRequest($this->api['kraken'] . 'channels/' . $channel)['profile_banner_background_color'];
    }

    /**
     * @param string $channel
     * @return bool
     */
    public function isPartner($channel = 'test_user1')
    {
        return $this->doRequest($this->api['kraken'] . 'channels/' . $channel)['partner'];
    }

    /**
     * @param string $channel
     * @return string
     */
    public function getChannelUrl($channel = 'test_user1')
    {
        return $this->doRequest($this->api['kraken'] . 'channels/' . $channel)['url'];
    }

    /**
     * @param string $channel
     * @return int
     */
    public function getChannelViewsCount($channel = 'test_user1')
    {
        return $this->doRequest($this->api['kraken'] . 'channels/' . $channel)['views'];
    }

    /**
     * @param string $channel
     * @return int
     */
    public function getChannelFollowersCount($channel = 'test_user1')
    {
        return $this->doRequest($this->api['kraken'] . 'channels/' . $channel)['followers'];
    }

    /**
     * @param string $channel
     * @return array
     */
    public function getChannelLinks($channel = 'test_user1')
    {
        return $this->doRequest($this->api['kraken'] . 'channels/' . $channel)['_links'];
    }

    /**
     * @param string $channel
     * @return array
     */
    public function getChannelFollows($channel = 'test_user1')
    {
        return $this->doRequest($this->api['kraken'] . 'channels/' . $channel . '/follows/');
    }


    /**
     * @param string $channel
     * @return bool
     */
    public function isStreamOnline($channel = 'test_user1')
    {
        $result = $this->doRequest($this->api['kraken'] . 'channels/' . $channel);

        if ($result['stream'] == null) {
            return false;
        }

        else return true;
    }

    /**
     * @param null $game
     * @param array $channel
     * @param array $params
     * @return array
     */
    public function getStreams($game    = null,
                               $channel = array(),
                               $params  = array
                               (
                                   'limit'       => 25,
                                   'offset'      => 0,
                                   'client_id'   => null,
                                   'stream_type' => 'all',
                                   'language'    => null
                               ))

    {

        if ($channel !== [])
            $channel_string = implode(",", $channel);

        if ($game != null)
            $append = '?game=' . urlencode($game);

        if ($channel != [])
            $append .= '&channel=' . $channel_string;

        if (array_key_exists('limit', $params) and $params['limit'] != 25)
            $append .= '&limit=' . $params['limit'];

        if (array_key_exists('offset', $params) and $params['offset'] != 0)
            $append .= '&offset=' . $params['offset'];

        if (array_key_exists('client_id', $params) and $params['client_id'] != null)
            $append .= '&client_id=' . $params['client_id'];

        if (array_key_exists('stream_type', $params) and $params['stream_type'] != 'all')
            $append .= '&stream_type=' . $params['stream_type'];

        if (array_key_exists('language', $params) and $params['language'] != null)
            $append .= '&language=' . $params['language'];

        return $this->curlRequest('streams/' . $append);
        //return $this->api['kraken'] . 'streams' . $append;
    }

    public function getGameStreamsCount($game)
    {
        return $this->getStreams($game)['_total'];
    }

    /**
     * @param string $channel
     * @return array
     */
    public function getStreamAll($channel = 'test_user1')
    {
        return $this->curlRequest('streams/' . $channel);
    }

    /**
     * @param string $channel
     * @return string
     */
    public function getStreamGameName($channel = 'test_user1')
    {
        return $this->curlRequest('streams/' . $channel)['stream']['game'];
    }

    /**
     * @param string $channel
     * @return string
     */
    public function getStreamViewers($channel = 'test_user1')
    {
        return $this->curlRequest('streams/' . $channel)['stream']['viewers'];
    }

    /**
     * @param string $channel
     * @return string
     */
    public function getStreamAverageFps($channel = 'test_user1')
    {
        return $this->curlRequest('streams/' . $channel)['stream']['viewers'];
    }

    /**
     * @param string $channel
     * @return int
     */
    public function getStreamDelay($channel = 'test_user1')
    {
        return $this->curlRequest('streams/' . $channel)['stream']['delay'];
    }

    /**
     * @param string $channel
     * @return int
     */
    public function getStreamVideoHeight($channel = 'test_user1')
    {
        return $this->curlRequest('streams/' . $channel)['stream']['video_height'];
    }

    /**
     * @param string $channel
     * @return string
     */
    public function getStreamCreatedAt($channel = 'test_user1')
    {
        return $this->curlRequest('streams/' . $channel)['stream']['created_at'];
    }

    /**
     * @param string $channel
     * @return int
     */
    public function getStreamId($channel = 'test_user1')
    {
        return $this->curlRequest('streams/' . $channel)['stream']['_id'];
    }

    /* --- PUBLIC METHODS END --- */


    /* --- PROTECTED METHODS START --- */



    /**
     * @param null $append
     * @return array
     */
    protected function curlRequest($append = null)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->api['kraken'] . $append);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $out = curl_exec($curl);
        curl_close($curl);

        $json_decode_array = json_decode($out, true);

        return $json_decode_array;
    }


    /**
     * @param string $client_id
     */
    public function setClientId($client_id)
    {
        $this->client_id = $client_id;
    }

    /**
     * @param string $client_secret
     */
    public function setClientSecret($client_secret)
    {
        $this->client_secret = $client_secret;
    }

    /**
     * @param string $redirect_url
     */
    public function setRedirectUrl($redirect_url)
    {
        $this->redirect_url = $redirect_url;
    }

    /**
     * @param boolean $oauth2
     */
    public function setOauth2($oauth2)
    {
        $this->oauth2 = $oauth2;
    }

    /**
     * @param boolean $state
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * @param string $state_value
     */
    public function setStateValue($state_value)
    {
        $this->state_value = $state_value;
    }


    /**
     * @param string $response_type
     */
    public function setResponseType($response_type)
    {
        $this->response_type = $response_type;
    }

    /**
     * @param string $grant_type
     * @return WellTwitch
     */
    public function setGrantType($grant_type)
    {
        $this->grant_type = $grant_type;
    }

    /**
     * @param array $scopes
     */
    protected function setScopes($scopes)
    {
        $this->scopes = $scopes;
    }

    /**
     * @return bool
     */
    public function isRequestIdentified()
    {
        if ($this->curlAuthRequest()['identified'] === true) {
            return true;
        }
        else {
            return false;
        }
    }


    /**
     * @return bool
     */
    public function isTwitchTokenValid()
    {
        if ($this->curlAuthRequest()['token']['valid'] === true) {
            return true;
        } else {
            return false;
        }
    }

    /* --- PROTECTED METHODS END --- */

}



