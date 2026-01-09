<?php

namespace GEData;

use Exception;


date_default_timezone_set("Asia/Shanghai");
const SDK_VERSION = '1.0.0';
const SDK_LIB_NAME = 'php';
const TRACK_TYPE_NORMAL = 'track';

const TRACK_TYPE_PROFILE = 'profile';
const USER_TYPE_SET = 'profile_set';
const USER_TYPE_SET_ONCE = 'profile_set_once';
const USER_TYPE_UNSET = 'profile_unset';
const USER_TYPE_APPEND = 'profile_append';
const USER_TYPE_UNIQUE_APPEND = 'profile_uniq_append';
const USER_TYPE_INCREMENT = 'profile_increment';
const USER_TYPE_DEL = 'profile_delete';
const USER_TYPE_NUM_MAX = 'profile_number_max';
const USER_TYPE_NUM_MIN = 'profile_number_min';


/**
 * Exception
 */
class GEDataException extends Exception
{
}

/**
 * Network exception
 */
class GEDataNetWorkException extends Exception
{
}


/**
 * Entry of SDK
 */
class GEAnalytics
{
    private $consumer;

    /**
     * Construct
     * @param GEAbstractConsumer $consumer
     */
    function __construct($consumer)
    {
        GELog::log("SDK init success");
        $this->consumer = $consumer;
    }

    /**
     * Set user properties. would overwrite existing names
     * @param string $client_id distinct ID
     * @param array $properties properties
     * @return boolean
     * @throws Exception exception
     */
    public function user_set($client_id, $properties = array())
    {
        return $this->add($client_id, USER_TYPE_SET, $properties);
    }

    /**
     * Set user properties, If such property had been set before, this message would be neglected.
     * @param string $client_id distinct ID
     * @param array $properties properties
     * @return boolean
     * @throws Exception exception
     */
    public function user_set_once($client_id, $properties = array())
    {
        return $this->add($client_id, USER_TYPE_SET_ONCE, $properties, null);
    }

    /**
     * To accumulate operations against the property
     * @param string $client_id distinct ID
     * @param array $properties properties
     * @return boolean
     * @throws Exception exception
     */
    public function user_increment($client_id, $properties = array())
    {
        return $this->add($client_id, USER_TYPE_INCREMENT, $properties);
    }

    /**
     * To accumulate max val against the property
     * @param string $client_id distinct ID
     * @param array $properties properties
     * @return boolean
     * @throws Exception exception
     */
    public function user_max($client_id, $properties = array())
    {
        return $this->add($client_id, USER_TYPE_NUM_MAX, $properties);
    }

    /**
     * To accumulate min val against the property
     * @param string $client_id distinct ID
     * @param array $properties properties
     * @return boolean
     * @throws Exception exception
     */
    public function user_min($client_id, $properties = array())
    {
        return $this->add($client_id, USER_TYPE_NUM_MIN, $properties);
    }

    /**
     * To add user properties of array type
     * @param string $client_id distinct ID
     * @param array $properties properties
     * @return boolean
     * @throws Exception exception
     */
    public function user_append($client_id, $properties = array())
    {
        return $this->add($client_id, USER_TYPE_APPEND, $properties);
    }

    /**
     * Append user properties to array type by unique.
     * @param string $client_id distinct ID
     * @param array $properties properties
     * @return boolean
     * @throws Exception exception
     */
    public function user_uniq_append($client_id, $properties = array())
    {
        return $this->add($client_id, USER_TYPE_UNIQUE_APPEND, $properties);
    }

    /**
     * Clear the user properties of users
     * @param string $client_id distinct ID
     * @param array $properties properties
     * @return boolean
     * @throws Exception exception
     */
    public function user_unset($client_id, $properties = array())
    {
        if ($this->isStrict() && is_null($properties)) {
            throw new GEDataException("property cannot be empty .");
        }
        return $this->add($client_id, USER_TYPE_UNSET, $properties);
    }

    /**
     * Delete a user, This operation cannot be undone
     * @param $client_id
     * @param $account_id
     * @param $properties
     * @return mixed
     * @throws Exception exception
     */
    public function user_del($client_id)
    {
        return $this->add($client_id, USER_TYPE_DEL, array());
    }

    /**
     * Report ordinary event
     * @param string $client_id distinct ID
     * @param string $event_name event name
     * @param array $properties properties
     * @return boolean
     * @throws Exception exception
     */
    public function track($client_id, $event_name, $properties = array())
    {
        $this->checkEventName($event_name);
        return $this->add($client_id, TRACK_TYPE_NORMAL, $properties, $event_name);
    }


    /**
     * Check event name
     * @throws Exception exception
     */
    private function checkEventName($eventName)
    {
        if ($this->isStrict() && (!is_string($eventName) || empty($eventName))) {
            throw new GEDataException("event name is not be empty");
        }
    }


    /**
     * @param $client_id
     * @param $type string
     * @param $event_name string
     * @param $event_id string
     * @param $properties array
     * @return mixed
     * @throws Exception exception
     */
    private function add($client_id, $type, $properties, $event_name = null)
    {

        $event = array();

        if ($client_id) {
            $event['client_id'] = $client_id;
        }

        // 获取当前时间的毫秒数时间戳
        $microtime = microtime(true);
        // 将时间戳转换为可读的格式
        $milliseconds = sprintf('%06d', ($microtime - floor($microtime)) * 1000000);
        $timestamp = floor($microtime) . $milliseconds;
        if ($type == TRACK_TYPE_NORMAL) {
            $event_list_item = array();
            $event_list_item['event'] = $event_name;
            $event_list_item['type'] = $type;
            $event_list_item['time'] = floor($timestamp / 1000);
            $properties['$lib'] = SDK_LIB_NAME;
            $properties['$lib_version'] = SDK_VERSION;
            $event_list_item['properties'] = $properties;
            $event['event_list'] = array($event_list_item);
        } else {
            $event_list_item = array();
            $event_list_item['event'] = $type;
            $event_list_item['type'] = TRACK_TYPE_PROFILE;
            $event_list_item['time'] = floor($timestamp / 1000);
            $properties['$lib'] = SDK_LIB_NAME;
            $properties['$lib_version'] = SDK_VERSION;
            $event_list_item['properties'] = $properties;
            $event['event_list'] = array($event_list_item);
        }

        $jsonStr = json_encode($event);

        return $this->consumer->send($jsonStr);
    }


    /**
     * report data immediately
     */
    public function flush()
    {
        $this->consumer->flush();
    }

    /**
     * close and exit sdk
     */
    public function close()
    {
        $this->consumer->close();
        GELog::log("SDK close");
    }

    /**
     * @return bool get strict status
     */
    private function isStrict()
    {
        return $this->consumer->getStrictStatus();
    }
}

/**
 * Abstract consumer
 */
abstract class GEAbstractConsumer
{
    /**
     * @var bool $strict check properties or not
     * true: the properties which invalidate will be dropped.
     * false: upload data anyway
     */
    protected $strict = false;

    /**
     * Get strict status
     * @return bool
     */
    public function getStrictStatus()
    {
        return $this->strict;
    }

    /**
     * report data
     * @param string $message data
     * @return bool
     */
    public abstract function send($message);

    /**
     * report data immediately
     * @return bool
     */
    public function flush()
    {
        return true;
    }

    /**
     * close and release resource
     * @return bool
     */
    public abstract function close();
}


/**
 * upload data to TE by http. not support multiple thread
 */
class GEBatchConsumer extends GEAbstractConsumer
{
    private $url;
    private $buffers;
    private $maxSize;
    private $requestTimeout;
    private $compress = true;
    private $retryTimes;
    private $isThrowException = false;
    private $cacheBuffers;
    private $cacheCapacity;

    /**
     * init BatchConsumer
     * @param string $server_url server url
     * @param int $max_size flush event count each time
     * @param int $retryTimes : retry times, default 3
     * @param int $request_timeout : http timeout, default 5s
     * @param int $cache_capacity : Multiple of $max_size, It determines the cache size
     * @throws GEDataException
     */
    function __construct($server_url, $max_size = 20, $retryTimes = 3, $request_timeout = 5, $cache_capacity = 50)
    {
        GELog::log("Batch consumer init success. receiverUrl:" . $server_url);

        $this->buffers = array();
        $this->maxSize = $max_size;
        $this->retryTimes = $retryTimes;
        $this->requestTimeout = $request_timeout;
        $this->cacheBuffers = array();
        $this->cacheCapacity = $cache_capacity;
        $this->url = $server_url;
        $this->strict = false;
    }

    /**
     * @throws GEDataNetWorkException
     * @throws GEDataException
     */
    public function __destruct()
    {
        $this->flush();
    }

    /**
     * @param $message
     * @return bool|null
     * @throws GEDataException
     * @throws GEDataNetWorkException
     */
    public function send($message)
    {
        $this->buffers[] = $message;
        if (count($this->buffers) >= $this->maxSize) {
            return $this->flush();
        } else {
            GELog::log("Enqueue data: $message");
            return null;
        }
    }

    /**
     * Flush data
     *
     * @param $flag
     * @return bool
     * @throws GEDataException
     * @throws GEDataNetWorkException
     */
    public function flush($flag = false)
    {
        GELog::log("Flush data");
        if (empty($this->buffers) && empty($this->cacheBuffers)) {
            return true;
        }
        if ($flag || count($this->buffers) >= $this->maxSize || count($this->cacheBuffers) == 0) {
            $sendBuffers = $this->buffers;
            $this->buffers = array();
            $this->cacheBuffers[] = $sendBuffers;
        }
        while (count($this->cacheBuffers) > 0) {
            $sendBuffers = $this->cacheBuffers[0];

            try {
                $this->doRequest($sendBuffers);
                array_shift($this->cacheBuffers);
                if ($flag) {
                    continue;
                }
                break;
            } catch (GEDataNetWorkException $netWorkException) {
                if (count($this->cacheBuffers) > $this->cacheCapacity) {
                    array_shift($this->cacheBuffers);
                }

                if ($this->isThrowException) {
                    throw $netWorkException;
                }
                return false;
            } catch (GEDataException $dataException) {
                array_shift($this->cacheBuffers);

                if ($this->isThrowException) {
                    throw $dataException;
                }
                return false;
            }
        }
        return true;
    }

    /**
     * Close consumer
     * @throws GEDataNetWorkException
     * @throws GEDataException
     */
    public function close()
    {
        $this->flush(true);
        GELog::log("Batch consumer close");
    }

    public function setCompress($compress = true)
    {
        $this->compress = $compress;
    }

    public function setFlushSize($max_size = 20)
    {
        $this->maxSize = $max_size;
    }

    public function openThrowException()
    {
        $this->isThrowException = true;
    }

    /**
     * @throws GEDataNetWorkException
     * @throws GEDataException
     */
    private function doRequest($message_array)
    {

        $consoleMessages = implode(PHP_EOL, $message_array);
        GELog::log("Send data, request: [\n$consoleMessages\n]");
        $client_map = array();
        foreach ($message_array as $j) {
            $i = json_decode($j, true);
            $client_id = $i['client_id'];
            $event_list = $i['event_list'];
            if (isset($client_map[$client_id])) {
                $client_map[$client_id] = array_merge($client_map[$client_id], $event_list);
            } else {
                $client_map[$client_id] = $event_list;
            }

        }

        foreach ($client_map as $client_id => $event_list) {
            $batch = array(
                "client_id" => $client_id,
                "event_list" => $event_list,
            );


            $ch = curl_init($this->url);

            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 6);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->requestTimeout);

            if ($this->compress) {
                $data = gzencode(json_encode($batch));
            } else {
                $data = json_encode($batch);
            }
            $compressType = $this->compress ? "gzip" : "none";
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            //headers
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("GE-Integration-Type:PHP", "GE-Integration-Version:" . SDK_VERSION,
                "GE-Integration-Count:" . count($batch), "Gravity-Content-Compress:" . $compressType,));

            //https
            $pos = strpos($this->url, "https");
            if ($pos === 0) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            }

            // send request
            $curreyRetryTimes = 0;
            while ($curreyRetryTimes++ < $this->retryTimes) {
                $result = curl_exec($ch);
                GELog::log("Send data, response: $result");

                if (!$result) {
                    echo new GEDataNetWorkException("Cannot post message to server , error --> " . curl_error(($ch)));
                    continue;
                }
                // parse data
                $json = json_decode($result, true);
                GELog::log("Send data, response: $json");

                $curl_info = curl_getinfo($ch);

                curl_close($ch);
                if ($curl_info['http_code'] == 200) {
                    if ($json['code'] == 0) {
                        return;
                    } else {
                        GELog::log("Unexpected Return Code:" . $json['extra'] . " for: " . $message_array);
                        throw new GEDataException(print_r($json, true));
                    }
                } else {
                    echo new GEDataNetWorkException("failed, http_code: " . $curl_info['http_code']);
                }
            }
            throw new GEDataNetWorkException("retry " . $this->retryTimes . " times, but failed!");

        }


    }
}

/**
 * [Deprecated class]
 * @deprecated please use GEDebugConsumer
 */
class DebugConsumer extends GEDebugConsumer
{
}


/**
 * The data is reported one by one, and when an error occurs, the exception will be thrown
 */
class GEDebugConsumer extends GEAbstractConsumer
{
    private $url;
    private $requestTimeout;

    /**
     * init DebugConsumer
     * @param string $server_url server url
     * @param int $request_timeout http timeout, default 5s
     * @throws GEDataException
     */
    function __construct($server_url, $request_timeout = 5)
    {
        GELog::log("Debug consumer init success.  receiverUrl:" . $server_url);

        $parsed_url = parse_url($server_url);
        if ($parsed_url === false) {
            throw new GEDataException("Invalid server url");
        }

        $this->url = $server_url;

        $this->requestTimeout = $request_timeout;
        $this->strict = true;
    }

    /**
     * @throws GEDataNetWorkException
     * @throws GEDataException
     */
    public function send($message)
    {
        return $this->doRequest($message);
    }


    public function close()
    {
    }

    /**
     * @throws GEDataNetWorkException
     * @throws GEDataException
     */
    private function doRequest($message)
    {
        GELog::log("Send data, request: $message");

        $ch = curl_init($this->url);
        $data = $message;


        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 6);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->requestTimeout);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        //https
        $pos = strpos($this->url, "https");
        if ($pos === 0) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }

        $result = curl_exec($ch);
        GELog::log("Send data, response: $result");


        if (!$result) {
            throw new GEDataNetWorkException("Cannot post message to server , error -->" . curl_error(($ch)));
        }

        // parse data
        $json = json_decode($result, true);

        $curl_info = curl_getinfo($ch);

        curl_close($ch);
        if ($curl_info['http_code'] == 200) {
            if ($json['code'] == 0) {
                return true;
            } else {
                GELog::log("Unexpected Return Code:" . $json['extra'] . " for: " . $message);
                throw new GEDataException(print_r($json, true));
            }
        } else {
            throw new GEDataNetWorkException("failed. HTTP code: " . $curl_info['http_code'] . "\t return content :" . $result);
        }
    }
}

/**
 * [Deprecated class]
 * @deprecated please use GELog
 */
class GELogger extends GELog
{
}

/**
 * Log module
 */
class GELog
{
    static $enable = false;

    static function log()
    {
        if (self::$enable) {
            $params = implode("", func_get_args());
            $time = date("Y-m-d H:i:s", time());
            echo "[GEData][$time]: ", $params, PHP_EOL;
        }
    }
}