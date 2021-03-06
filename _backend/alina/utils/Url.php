<?php

namespace alina\utils;

use alina\Message;

class Url
{
    ##################################################
    #region URL's, Aliases, Routes
    static public function routeAccordance($url, array $vocabulary = [], $aliasToSystemRoute = TRUE)
    {
        $parsedUrlSource = parse_url($url);
        $pathSource      = $parsedUrlSource['path'];
        $pathSource      = trim($pathSource, '/');
        $pathRes         = '';
        foreach ($vocabulary as $aliasMask => $urlMask) {
            $compareWith       = ($aliasToSystemRoute) ? $aliasMask : $urlMask;
            $regularExpression = static::routeRegExp($compareWith);
            if (preg_match($regularExpression, $pathSource)) {
                if ($aliasToSystemRoute) {
                    $pathRes = static::aliasToUrl($aliasMask, $pathSource, $urlMask);
                }
                else {
                    $pathRes = static::urlToAlias($urlMask, $pathSource, $aliasMask);
                }
                $parsedUrlSource['path'] = $pathRes;
                $uri                     = static::un_parse_url($parsedUrlSource);

                return $uri;
            }
        }

        return $url;
    }

    static public function routeRegExp($string)
    {
        $parts             = explode('/', $string);
        $regularExpression = [];
        foreach ($parts as $v) {
            if ($v === ':p' || FALSE !== strpos($v, ':p')) {
                $regularExpression[] = '.+?';
            }
            else {
                $regularExpression[] = $v;
            }
        }
        $regularExpression = implode('\/', $regularExpression);
        $regularExpression = '/^' . $regularExpression . '$/i';

        return $regularExpression;
    }

    static public function aliasToUrl($aliasMask, $systemRoute, $systemRouteMask)
    {
        return static::routeConverter(
            $aliasMask,
            $systemRoute,
            $systemRouteMask
        );
    }

    static public function urlToAlias($systemRouteMask, $systemRoute, $aliasMask)
    {
        return static::routeConverter(
            $systemRouteMask,
            $systemRoute,
            $aliasMask
        );
    }

    static public function routeConverter($fromMask, $source, $toMask)
    {
        $fromMaskArray = explode('/', $fromMask);
        $sourceArray   = explode('/', $source);
        $toMaskArray   = explode('/', $toMask);
        $_parameters   = [];
        foreach ($fromMaskArray as $i => $pN) {
            if (FALSE !== strpos($pN, ':p')) {
                $_parameters[$pN] = $sourceArray[$i];
            }
        }
        $convertedResult = [];
        foreach ($toMaskArray as $i => $pN) {
            if (FALSE !== strpos($pN, ':p')) {
                $convertedResult[] = $_parameters[$pN];
            }
            else {
                $convertedResult[] = $pN;
            }
        }

        return implode('/', $convertedResult);
    }

    #endregion URL's, Aliases, Routes
    ##################################################
    #region PARSE_URL
    static public function un_parse_url(array $parsedUri)
    {
        $get          = function ($key) use ($parsedUri) {
            return isset($parsedUri[$key]) ? $parsedUri[$key] : '';
        };
        $pass         = $get('pass');
        $user         = $get('user');
        $userinfo     = (!empty($pass)) ? "$user:$pass" : $user;
        $port         = $get('port');
        $scheme       = $get('scheme');
        $query        = $get('query');
        $fragment     = $get('fragment');
        $arrAuthority = [
            !empty($userinfo) ? "$userinfo@" : '',
            $get('host'),
            $port ? ":$port" : '',
        ];
        $authority    = implode('', $arrAuthority);
        $arrRes       = [
            strlen($scheme) ? "$scheme:" : '',
            strlen($authority) ? "//$authority" : '',
            $get('path'),
            strlen($query) ? "?$query" : '',
            strlen($fragment) ? "#$fragment" : '',
        ];

        return implode('', $arrRes);
    }

    static public function cleanDomainWithProtocolAndPort($url)
    {
        $res    = $url;
        $res    = mb_strtolower($res);
        $parsed = parse_url($res);
        $res    = static::un_parse_url([
            'scheme' => isset($parsed['scheme']) ? $parsed['scheme'] : NULL,
            'host'   => isset($parsed['host']) ? $parsed['host'] : NULL,
            'port'   => isset($parsed['port']) ? $parsed['port'] : NULL,
        ]);

        return $res;
    }

    static public function cleanDomain($url)
    {
        $res = $url;
        $res = mb_strtolower($res);
        $res = str_replace(['http://', 'https://'], '', $res);
        $res = explode('/', $res)[0];
        $res = explode(':', $res)[0];

        return $res;
    }

    static public function cleanPath($url)
    {
        $res = $url;
        $res = parse_url($res, PHP_URL_PATH);
        $res = urldecode($res);

        //$res = mb_strtolower($res);
        return $res;
    }
    #endregion PARSE_URL
    ##################################################
    static public function addGetFromObject($url, $getObj)
    {
        $get = http_build_query($getObj);
        $uri = [
            'path'  => $url,
            'query' => $get,
        ];
        $res = Url::un_parse_url($uri);

        return $res;
    }
}
