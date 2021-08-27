<?php


class accessLogController
{
    private const PATTERN_FOR_RECORD = "/(\S+) (\S+) (\S+) \[([^:]+):(\d+:\d+:\d+) ([^\]]+)\] \"(\S+) (.*?) (\S+)\" (\S+) (\S+) (\".*?\") (\".*?\")/";

    private const FIELD_ID_URL         = 8;
    private const FIELD_ID_STATUS_CODE = 10;
    private const FIELD_ID_TRAFFIC     = 11;
    private const FIELD_ID_USER_AGENT  = 13;

    private const FIELD_LABEL_URL         = 'url';
    private const FIELD_LABEL_STATUS_CODE = 'status_code';
    private const FIELD_LABEL_TRAFFIC     = 'traffic';
    private const FIELD_LABEL_USER_AGENT  = 'user_agent';

    private const ELEMENTS_IN_DECODED_RECORD = 14;

    private const USER_AGENT_PART_BOT_GOOGLE  = 'googlebot';
    private const USER_AGENT_PART_BOT_YANDEX  = 'yandex.com/bots';
    private const USER_AGENT_PART_BOT_MAIL    = 'mail.ru_bot';
    private const USER_AGENT_PART_BOT_RAMBLER = 'stackrambler';
    private const USER_AGENT_PART_BOT_YAHOO   = 'ysearch/slurp';
    private const USER_AGENT_PART_BOT_MSN     = 'msnbot';
    private const USER_AGENT_PART_BOT_BING    = 'bingbot';

    private const SEARCH_SYSTEM_LABEL_GOOGLE  = 'Google';
    private const SEARCH_SYSTEM_LABEL_YANDEX  = 'Yandex';
    private const SEARCH_SYSTEM_LABEL_MAIL    = 'Mail';
    private const SEARCH_SYSTEM_LABEL_RAMBLER = 'Rambler';
    private const SEARCH_SYSTEM_LABEL_YAHOO   = 'Yahoo';
    private const SEARCH_SYSTEM_LABEL_MSN     = 'MSN';
    private const SEARCH_SYSTEM_LABEL_BING    = 'Bing';

    public function processLogFile(string $pathToLogFile): string {
        if(!$this->_isNonEmptyString($pathToLogFile)) {
            return $this->_error('Empty path to file');
        }
        if (!$this->_isReadableFile($pathToLogFile)) {
            return $this->_error('Wrong file');
        }

        $totalRecords = 0;
        $parseFails   = 0;
        $totalViews   = 0;
        $traffic      = 0;
        $uniqUrls     = [];
        $statusCodes  = [];
        $crawlers     = [];

        $fileStream = $this->_openFileStream($pathToLogFile);
        if (!is_resource($fileStream)) {
            return $this->_error('Troubles with file opening');
        }
        while (!$this->_isEndOfFile($fileStream)) {
            $fileRow = $this->_getRowFromFile($fileStream);
            if (!$this->_isNonEmptyString($fileRow)) {
                continue;
            }

            $totalRecords++;
            $record = $this->_parseAndFormatRecord($fileRow);
            if (count($record) === 0) {
                $parseFails++;
                continue;
            }

            if (!in_array($record[self::FIELD_LABEL_URL], $uniqUrls)) {
                $uniqUrls[] = $record[self::FIELD_LABEL_URL];
            }

            if (array_key_exists($record[self::FIELD_LABEL_STATUS_CODE], $statusCodes)) {
                $statusCodes[$record[self::FIELD_LABEL_STATUS_CODE]]++;
            } else {
                $statusCodes[$record[self::FIELD_LABEL_STATUS_CODE]] = 1;
            }

            $searchSystem = $this->_determineSearchBot($record[self::FIELD_LABEL_USER_AGENT]);
            if ($searchSystem === null) {
                $totalViews++;
            } else {
                if (array_key_exists($searchSystem, $crawlers)) {
                    $crawlers[$searchSystem]++;
                } else {
                    $crawlers[$searchSystem] = 1;
                }
            }

            $traffic += $record[self::FIELD_LABEL_TRAFFIC];
        }

        return $this->_success([
            'total'        => $totalRecords,
            'parse_fails'  => $parseFails,
            'views'        => $totalViews,
            'traffic'      => $traffic,
            'urls'         => count($uniqUrls),
            'status_codes' => $statusCodes,
            'crawlers'     => $crawlers
        ]);
    }

    private function _isNonEmptyString(?string $value): bool {
        return is_string($value) && $value !== '';
    }

    private function _determineSearchBot(string $userAgent): ?string {
        if (strpos($userAgent, self::USER_AGENT_PART_BOT_GOOGLE) !== false) {
            return self::SEARCH_SYSTEM_LABEL_GOOGLE;
        } elseif (strpos($userAgent, self::USER_AGENT_PART_BOT_YANDEX) !== false) {
            return self::SEARCH_SYSTEM_LABEL_YANDEX;
        } elseif (strpos($userAgent, self::USER_AGENT_PART_BOT_MAIL) !== false) {
            return self::SEARCH_SYSTEM_LABEL_MAIL;
        } elseif (strpos($userAgent, self::USER_AGENT_PART_BOT_RAMBLER) !== false) {
            return self::SEARCH_SYSTEM_LABEL_RAMBLER;
        } elseif (strpos($userAgent, self::USER_AGENT_PART_BOT_YAHOO) !== false) {
            return self::SEARCH_SYSTEM_LABEL_YAHOO;
        } elseif (strpos($userAgent, self::USER_AGENT_PART_BOT_MSN) !== false) {
            return self::SEARCH_SYSTEM_LABEL_MSN;
        } elseif (strpos($userAgent, self::USER_AGENT_PART_BOT_BING) !== false) {
            return self::SEARCH_SYSTEM_LABEL_BING;
        }

        return null;
    }

    /**
     * @param string $fileRow
     * @return array
     */
    private function _parseAndFormatRecord(string $fileRow): array {
        $parsedFileRow = [];
        preg_match (self::PATTERN_FOR_RECORD, $fileRow, $parsedFileRow);
        if (count($parsedFileRow) === self::ELEMENTS_IN_DECODED_RECORD) {
            return [
                self::FIELD_LABEL_URL         => $parsedFileRow[self::FIELD_ID_URL],
                self::FIELD_LABEL_STATUS_CODE => $parsedFileRow[self::FIELD_ID_STATUS_CODE],
                self::FIELD_LABEL_TRAFFIC     => (int) $parsedFileRow[self::FIELD_ID_TRAFFIC],
                self::FIELD_LABEL_USER_AGENT  => strtolower($parsedFileRow[self::FIELD_ID_USER_AGENT]),
            ];
        }
        return [];
    }

    /**
     * @param resource $fileStream
     * @return string
     */
    private function _getRowFromFile($fileStream): string {
        return fgets($fileStream);
    }

    /**
     * @param string $pathToFile
     * @return resource
     */
    private function _openFileStream(string $pathToFile) {
        return fopen($pathToFile, 'r');
    }

    private function _isEndOfFile( $fileStream): bool {
        return feof($fileStream);
    }

    private function _isReadableFile(string $pathToFile): bool {
        return is_readable($pathToFile);
    }

    private function _error(string $message): string {
        return "Error: {$message}!";
    }

    private function _success(array $result): string {
        return json_encode($result, JSON_PRETTY_PRINT);
    }
}