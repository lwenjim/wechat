<?php

namespace App\Http\Controllers;

use App\Models\Weather;

use App\Models\LocationMap;

class WeatherController extends Controller
{

    public function main($longitude, $latitude){
         //获取地址
         $address = $this->getLocation($longitude, $latitude);
         if (empty($address)) {
             $this->error('经纬度定位城市失败', 400);
         }
         if ($address['country_code_iso']!='CHN') {
             $this->error('国外', 400);
         }
         $data = LocationMap::where('province','like',self::province($address['province']))->where('city','like',self::city($address['city']))->where('area','like',$address['district'])->first() ?? LocationMap::where('province','like',self::province($address['province']))->where('city','like',self::city($address['city']))->first();
         return is_null($data) ? '定位失败' : ['id'=>$data->city_id, 'address'=>$address];
    }

    private static function province($province){
        return '%'.str_replace('市', '', str_replace('省', '', $province)).'%';
    }

    private static function city($city){
        return "%".str_replace('市', '', $city).'%';
    }

	public function main_co($longitude, $latitude){
    	//获取地址
    	$address = $this->getLocation($longitude, $latitude);
    	if (empty($address)) {
    		$this->error('经纬度定位城市失败', 400);
    	}
    	if ($address['country_code_iso']!='CHN') {
    		$this->error('国外', 400);
    	}
    	//判断该地区天气信息是否存在
    	$weather = Weather::where('adcode', $address['adcode'])->first();
    	if (is_null($weather)) {
    		$weather = $this->getLiveWeather($longitude, $latitude);
    		if (empty($weather)) {
    			$this->error('获取天气信息失败', 400);
    		}
    		$weather = [
						'adcode'		=>	$address['adcode'],
						'province'		=>	$address['province'],
						'city'			=>	$address['city'],
						'district'		=>	$address['district'],
						'street'		=>	$address['street'],
						'weather'		=>	json_encode($weather),
						'expire_time'	=>	time()+10800,
						];
    		$id = weather::create($weather);
    	}
    	$weather['weather'] = json_decode($weather['weather']);
		return $weather;
    }

	private function getLocation($longitude, $latitude){
		try {
			$globalApi = 'http://api.map.baidu.com/geocoder/v2/?output=json&ak=iplFs3MS2smrZEyS1uYvw0VPSzvH0uPA&coordtype=wgs84ll&location=';
            $jsonResult = $this->http($globalApi . $latitude . "," . $longitude);
            return [
            		'country_code_iso'	=>	$jsonResult['result']['addressComponent']['country_code_iso'],
            		'province'			=>	$jsonResult['result']['addressComponent']['province'],
            		'city'				=>	$jsonResult['result']['addressComponent']['city'],
            		'district'			=>	$jsonResult['result']['addressComponent']['district'],
            		'street'			=>	$jsonResult['result']['addressComponent']['street'],
            		'adcode'			=>	$jsonResult['result']['addressComponent']['adcode'],
            		];
        } catch (\Exception $e) {
            return [];
        }
	}

	private function getLiveWeather($longitude, $latitude){
		$weather = new \App\Services\Weather\Weather();
        try {
            $response = $weather->getWeather($longitude, $latitude);
            $json_result = json_decode($response->getBody(), true);
            $result = $json_result['showapi_res_body'];
            $return['currentTempture'] = $result['now']['temperature'];
            $return['precipitation'] = $result['now']['weather'];
            $return['temperaturedesc'] = $result['now']['weather'];
            $return['temperaturemax'] = $result['f1']['day_air_temperature'];
            $return['pm25'] = $result['now']['aqiDetail']['pm2_5'];
            $return['3hourforcast'] = $result['f1']['3hourForcast'];
            return $return;
        } catch (\Exception $e) {
            return [];
        }
	}

	
}