<?php

/*
 * This file is part of oui_player_abcnews,
 * a oui_player v2+ extension to easily create
 * HTML5 customizable video and audio players in Textpattern CMS.
 *
 * https://github.com/NicolasGraph/oui_player_html
 *
 * Copyright (C) 2018 Nicolas Morand
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; version 2 of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA..
 */

/**
 * Video
 *
 * Manages HTML5 <video> player.
 *
 * @package Oui\Player
 */

namespace Oui\Player {

    if (class_exists('Oui\Player\Provider')) {

        class Audio extends Provider
        {
            protected static $patterns = array(
                'filename' => array(
                    'scheme' => '#^((?!(http|https)://(www\.)?)\S+\.(mp3|ogg|oga|wav|aac|flac))$#i',
                    'id'     => '1',
                ),
                'url' => array(
                    'scheme' => '#^(((http|https):\/\/(www.)?)\S+\.(mp3|ogg|oga|wav|aac|flac))$#i',
                    'id'     => '1',
                ),
            );
            protected static $mimeTypes = array(
                'mp3'  => 'audio/mp3',
                'ogg'  => 'video/ogg',
                'oga'  => 'video/ogg',
                'wav'  => 'video/wave',
                'aac'  => 'audio/aac',
                'flac' => 'audio/flac',
            );
            protected static $src = '';
            protected static $glue = ' ';
            protected static $dims = array(
                'width'     => array(
                    'default' => '',
                ),
            );
            protected static $params = array(
                'autoplay' => array(
                    'default' => '0',
                    'valid'   => array('0', '1'),
                ),
                'controls' => array(
                    'default' => '0',
                    'valid'   => array('0', '1'),
                ),
                'loop'     => array(
                    'default' => '0',
                    'valid'   => array('0', '1'),
                ),
                'muted'    => array(
                    'default' => '0',
                    'valid'   => array('0', '1'),
                ),
                'preload'  => array(
                    'default' => 'auto',
                    'valid'   => array('none', 'metadata', 'auto'),
                ),
                'volume'   => array(
                    'default' => '',
                    'valid'   => 'number',
                ),
            );

            /**
             * {@inheritdoc}
             */

            public static function getMimeType($extension)
            {
                return static::$mimeTypes[$extension];
            }

            /**
             * {@inheritdoc}
             */

            public function getPlayerParams()
            {
                $params = array();

                foreach (self::getParams() as $param => $infos) {
                    $pref = get_pref(strtolower(str_replace('\\', '_', get_class($this))) . '_' . $param);
                    $default = $infos['default'];
                    $value = isset($this->config[$param]) ? $this->config[$param] : '';

                    // Add attributes values in use or modified prefs values as player parameters.
                    if ($value === '' && $pref !== $default) {
                        if ($infos['valid'] === array('0', '1')) {
                            $params[] = $param;
                        } else {
                            $params[] = $param . '="' . $pref . '"';
                        }
                    } elseif ($value !== '') {
                        if ($infos['valid'] === array('0', '1')) {
                            $params[] = $param;
                        } else {
                            $params[] = $param . '="' . $value . '"';
                        }
                    }
                }

                return $params;
            }

            /**
             * Get the player code
             */

            public function getSources()
            {
                $infos = $this->getInfos();

                $sources = array();

                foreach ($infos as $play => $info) {
                    extract($info);

                    if ($type === 'url') {
                        $sources[] = $play;
                    } else {
                        if ($type === 'id') {
                            $file = fileDownloadFetchInfo(
                                'id = '.intval($play).' and created <= '.now('created')
                            );
                        } elseif ($type === 'filename') {
                            $file = fileDownloadFetchInfo(
                                "filename = '".doSlash($play)."' and created <= ".now('created')
                            );
                        }

                        $sources[] = filedownloadurl($file['id'], $file['filename']);
                    }
                }

                return $sources;
            }

            /**
             * {@inheritdoc}
             */

            public function getPlayer($wraptag = null, $class = null)
            {
                if ($sources = $this->getSources()) {
                    $src = $sources[0];

                    unset($sources[0]);

                    $sourcesStr = array();

                    foreach ($sources as $source) {
                        $sourcesStr[] = '<source src="' . $source . '" type="' . self::getMimeType(pathinfo($source, PATHINFO_EXTENSION)). '">';
                    }

                    $params = $this->getPlayerParams();
                    $dims = $this->getSize();

                    extract($dims);

                    is_string($width) ?: $width .= 'px';

                    $style = !empty($width) ? ' style="width:' . $width . '"' : '';

                    $player = sprintf(
                        '<audio src="%s"%s%s>%s%s</audio>',
                        $src,
                        $style,
                        (empty($params) ? '' : ' ' . implode(self::getGlue(), $params)),
                        ($sourcesStr ? n . implode(n, $sourcesStr) : ''),
                        n . gtxt(
                            'oui_player_html_player_not_supported',
                            array(
                                '{player}' => '<audio>',
                                '{src}'    => $src,
                                '{file}'   => basename($src),
                            )
                        ) . n
                    );

                    return ($wraptag) ? doTag($player, $wraptag, $class) : $player;
                }
            }
        }
    }
}

namespace {
    function oui_audio($atts) {
        return oui_player(array_merge(array('provider' => 'audio'), $atts));
    }

    function oui_if_audio($atts, $thing) {
        return oui_if_player(array_merge(array('provider' => 'audio'), $atts), $thing);
    }
}
