<?php
/**
 * @copyright Copyright (c) 2011 - 2014 Oleksandr Torosh (http://wezoom.net)
 * @author Oleksandr Torosh <web@wezoom.net>
 */

namespace Application\Mvc\Helper;

use Cms\Model\Language;
use Phalcon\Mvc\User\Component;

class LangSwitcher extends Component
{

    public function render($lang, $string)
    {
        $router = $this->getDI()->get('router');
        $view = $this->getDI()->get('view');
        $url = $this->getDI()->get('url');

        $requestQuery = new RequestQuery();
        $matched_route = $router->getMatchedRoute();
        if ($matched_route) {
            $route_name = $matched_route->getName();
            $route_name_changed = $this->changeRouteName($route_name, $lang);

            $route_exitsts = $router->getRouteByName($route_name_changed);
            if ($route_exitsts) {
                $url_args = array();
                $url_args['for'] = $route_name_changed;

                $route_params = $router->getParams();

                if ($route_params) {
                    foreach ($route_params as $param_key => $param_val) {
                        $url_args[$param_key] = $param_val;
                    }

                    $mlSlug = $this->getMLSlug($matched_route, $route_params, $lang);
                    $url_args['slug'] = $mlSlug;
                }

                $href = $url->get($url_args);
            } else {
                $uri = $router->getRewriteUri();
                $href = $uri . $requestQuery->getSymbol() . '?lang=' . $lang;
            }
        } else {
            $uri = $router->getRewriteUri();
            $href = $uri . $requestQuery->getSymbol() . '?lang=' . $lang;
        }

        if ($lang == LANG) {
            $html = '<span>' . $string . '</span>';
        } elseif ($view->disabledLang == $lang) {
            $html = '<span class="disabled">' . $string . '</span>';
        } else {
            $html = '<a href="' . $href . '">' . $string . '</a>';
        }

        return $html;

    }

    private function changeRouteName($route_name, $lang)
    {
        $iso_array = Language::findCachedLanguagesIso();
        if (!empty($iso_array)) {
            foreach ($iso_array as $iso) {
                $route_name = str_replace('_' . $iso, '', $route_name);
            }
        }
        return $route_name . '_' . $lang;
    }

    private function getMLSlug($matched_route, $route_params, $lang)
    {
        $paths = $matched_route->getPaths();
        $module = $paths['module'];

        $curSLug = $route_params['slug'];
        // query moudle entity id.
        $query = "key = 'slug' and value = '$curSLug'";
        $modelName = \Phalcon\Text::camelize($module);
        $modelTranslateName = "{$modelName}Translate";
        $translateModel = "{$modelName}\Model\Translate\\{$modelName}Translate";
        $slugRecord = $translateModel::findFirst([$query]);
        // query all slug.
        $mlSlug = $translateModel::findFirst([
            'conditions' => 'foreign_id = :id: AND lang = :lang: AND key = "slug"',
            'bind'       => [
                'id'     => $slugRecord->foreign_id,
                'lang'   => $lang
            ],
        ]);

        return $mlSlug->value;
    }

}
