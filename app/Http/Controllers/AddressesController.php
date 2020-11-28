<?php
namespace App\Http\Controllers;

use\App\Models\City;
use\App\Models\Region;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Laravel\Lumen\Routing\Controller as BaseController;

class AddressesController extends BaseController
{
    protected $request;

    public function __construct(Request $request){
        $this->request = $request;
    }

    public function getAddresses()
    {
        $input_array = $this->request->all();
        if ($input_array) {
            $this->validate($this->request, [
                'id' => 'required|Integer',
            ]);
            $city = City::where('region_id', $input_array['id'])
                    ->with('region')
                    ->get()
                    ->toArray();
            if (!$city) {
                return 'NOT FOUND';
            }
        } else {
            $city = City::with('region')
                    ->get()
                    ->toArray();
            if (!$city){
                return 'NOT FOUND';
            }
        }
        $result = array_map(function($item){
            return [
                'city' => Arr::get($item, 'city'),
                'region' => Arr::get($item, 'region.region')
            ];
        }, $city);
        return json_encode($result);
    }

    public function searchAddresses()
    {
        $this->validate($this->request, [
            'latitude' => 'required|Numeric',
            'longitude' => 'required|Numeric',
        ]);
        $input_array = $this->request->all();
        $area = $this->getAPI($input_array['latitude'], $input_array['longitude'], 'administrative_area_level_1');
        $city = $this->getAPI($input_array['latitude'], $input_array['longitude'], 'locality');
        if ($area && $city){
            $this->saveDB($area, $city);
            $result = json_encode([
                'city' => $city,
                'area' => $area
            ]);
            return $result;
        }
        return 'NOT FOUND';
    }

    private function getAPI($latitude, $longitude, $type)
    {
        $endpoit = 'https://maps.googleapis.com/maps/api/geocode/json';
        $response = Http::get($endpoit, 
            [
                'latlng' => $latitude . ',' . $longitude,
                'key' => 'AIzaSyCh2ZrUe98IHEs4V3rNUhTsvpTZhhuhHgk',
                'result_type' => $type
            ]);
        if ($response->ok()){
            $result = json_decode($response->body(), true);
            return Arr::get($result, 'results.0.address_components.0.long_name');
        }
        return false;
    }

    private function saveDB($search_region, $search_city)
    {
        $region = Region::where('region', $search_region)
                    ->get()
                    ->toArray();
        if (!$region){
            $region = new Region;
            $region->region = $search_region;
            $region->save();
            $city = new City;
            $city->city = $search_city;
            $city->region_id = $region->id;
            $city->save();
        } else {
            $city = City::where(function ($query) use ($region, $search_city) {
                    $query->where('region_id', Arr::get($region, 'id'))
                        ->orWhere('city', $search_city);
                    })
                    ->get()
                    ->toArray();
            if (!$city){
                $city = new City;
                $city->city = $search_city;
                $city->region_id = Arr::get($region, 'id');
                $city->save();
            }
        }
        return true;
    }
}
