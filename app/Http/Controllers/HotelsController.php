<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;


class HotelsController extends Controller
{

    // Function to find the Hotels from BestHotel and TopHotel
    public function find_hotel(Request $request){

        try {

            // Check your Validation 
            $request->validate([
                'city' => 'required|size:3',
                'from_date' => 'required|date|after:yesterday',
                'to_date' => 'required|date|after:fromDate',
                'adults_number' => 'required|integer',
            ]);


            // Rest array
            $OurHotels = []; 
            

            //Get Hotesls from BestHotels
            $BestHotelsRequest =  new Request();
            $BestHotelsRequest->merge(['city'=>$request->city,'fromDate'=>$request->from_date,'toDate'=>$request->to_date,'numberOfAdults'=>$request->adults_number]);
            $BestHotels = $this->BestHotelAPI($BestHotelsRequest);
            $BestHotelsCount  = count($BestHotels);

            for($i=0; $i<$BestHotelsCount ; $i++){
                array_push($OurHotels,
                            array(
                                  'provider' => 'BestHotels',
                                  'hotelName' => $BestHotels[$i]['hotel'],
                                  'fare' => $this->calculate_hotel_fare_per_day($request->from_date,$request->to_date,$request->adults_number,$BestHotels[$i]['hotelFare']),
                                  'amenities' => explode(",",$BestHotels[$i]['roomAmenities']), // change amenities from string to array 
                                  'rate' => intVal($BestHotels[$i]['hotelRate'])
                                )
                            );
            }

            //Get Hotesls from TopHotels
            $TopHotelsRequest =  new Request();
            $TopHotelsRequest->merge(['city'=>$request->city,'from'=>$request->from_date,'To'=>$request->to_date,'adultsCount'=>$request->adults_number]);
            $TopHotels = $this->TopHotelsAPI($TopHotelsRequest);            
            $TopHotelsCount = count($TopHotels);

            for($j=0; $j<$TopHotelsCount ; $j++){
                array_push($OurHotels, 
                            array(
                                'provider' => 'TopHotels',
                                'hotelName' => $TopHotels[$j]['hotelName'],
                                'fare' => $TopHotels[$j]['price'],
                                'amenities' => $TopHotels[$j]['amenities'],
                                'rate' => strlen($TopHotels[$j]['rate']),
                            )
                );
            }

            // sorting array desc order by rating
            array_multisort( array_column($OurHotels, "rate"), SORT_DESC, $OurHotels );

            // remova rate from the array
            $finalCount = count($OurHotels);
            for($k=0; $k<$finalCount; $k++){
                unset($OurHotels[$k]['rate']);
            }
            
        
            // return Json with status code and result data 
            return response()->json(['status_code'=>200,'result'=>$OurHotels]); 
                  
        } 
        catch (ValidationException $e) {
            return ($e->errors());
        }  


    }

    public function BestHotelAPI(Request $request){

        /**
         * static data insted of DB 
         */
       $hotelsSourceData = [
           [
               'hotel' => 'BestHotel_1',
               'hotelRate'=>'1',
               'hotelFarePerDay' => '50',
               'roomAmenities' => 'bed,TV,bathroom',
               'city' => 'AAC',
           ],
           [
                'hotel' => 'BestHotel_2',
                'hotelRate'=>'5',
                'hotelFarePerDay' => '300',
                'roomAmenities' => 'bed,TV,bathroom,livingroom,kitchen',
                'city' => 'ABS',
            ],
            [
                'hotel' => 'BestHotel_3',
                'hotelRate'=>'4',
                'hotelFarePerDay' => '200',
                'roomAmenities' => 'bed,bathroom',
                'city' => 'ALY',
            ],
            [
                'hotel' => 'BestHotel_4',
                'hotelRate'=>'5',
                'hotelFarePerDay' => '600',
                'roomAmenities' => 'bed,TV,bathroom,fridge,ariconditioner',
                'city' => 'ASW',
            ],
            [
                'hotel' => 'BestHotel_5',
                'hotelRate'=>'3',
                'hotelFarePerDay' => '20',
                'roomAmenities' => 'bed,TV',
                'city' => 'AAC',
            ],
            [
                'hotel' => 'BestHotel_6',
                'hotelRate'=>'2',
                'hotelFarePerDay' => '10',
                'roomAmenities' => 'bed',
                'city' => 'ALY',
            ]
       ];


       /**
        *   start API work 
        */


        try {

            // check for validation 

            $request->validate([
                'city' => 'required|size:3',
                'fromDate' => 'required|date|after:yesterday',
                'toDate' => 'required|date|after:fromDate',
                'numberOfAdults' => 'required|integer',
            ]);

            // get matched Hotels depend of city sent by the user in the API
            // Should be SQL statemnt or ORM query if we work with DB
            $matchedHotelsByCity = array_keys(array_column($hotelsSourceData, 'city'), strtoupper($request->city));
            $matchedHotelsByCityCount = count($matchedHotelsByCity);

            $best_hotels = array();

            // loop on the matched hotels to add thiem to the result array
            for($i=0 ; $i<$matchedHotelsByCityCount; $i++){

                $farePerDay = $hotelsSourceData[$matchedHotelsByCity[$i]]['hotelFarePerDay'];
                $hotelFare = $this->calculate_hotel_total_fare( $request->toDate , $request->fromDate,$farePerDay, $request->numberOfAdults ); 
                array_push($best_hotels,
                        array( 
                            'hotel' => $hotelsSourceData[$matchedHotelsByCity[$i]]['hotel'],
                            'hotelRate' => $hotelsSourceData[$matchedHotelsByCity[$i]]['hotelRate'],
                            'hotelFare' => round($hotelFare,2), // rounf the result to 2 decimals 
                            'roomAmenities' => $hotelsSourceData[$matchedHotelsByCity[$i]]['roomAmenities'],
                        
                        )
                );
            }

            // return the array
         return  $best_hotels; 
        }
        catch (ValidationException $e) {
            return ($e->errors());
        }  
    }

  

    public function TopHotelsAPI(Request $request){

        /**
         * static data insted of DB 
         */
        $hotelsSourceData = [
            [
                'hotel' => 'TopHotel_1',
                'hotelRate'=>'*',
                'hotelFarePerDay' => '50',
                'roomAmenities' => array('bed','TV','bathroom'),
                'city' => 'ABS',
                'discount'=> '10%',
            ],
            [
                 'hotel' => 'TopHotel_2',
                 'hotelRate'=>'*****',
                 'hotelFarePerDay' => '300',
                 'roomAmenities' => array('bed','TV','bathroom','livingroom','kitchen'),
                 'city' => 'ASW',
                 'discount'=> '3%',
             ],
             [
                 'hotel' => 'TopHotel_3',
                 'hotelRate'=>'***',
                 'hotelFarePerDay' => '200',
                 'roomAmenities' => array('bed','bathroom'),
                 'city' => 'AAC',
                 'discount'=> '0',
             ],
             [
                 'hotel' => 'TopHotel_4',
                 'hotelRate'=>'*****',
                 'hotelFarePerDay' => '600',
                 'roomAmenities' => array('bed','TV','bathroom','fridge','ariconditioner'),
                 'city' => 'AAC',
                 'discount'=> '60%',
             ],
             [
                 'hotel' => 'TopHotel_5',
                 'hotelRate'=>'***',
                 'hotelFarePerDay' => '20',
                 'roomAmenities' => array('bed','TV'),
                 'city' => 'ALY',
                 'discount'=> '6%',
             ],
             [
                 'hotel' => 'TopHotel_6',
                 'hotelRate'=>'**',
                 'hotelFarePerDay' => '10',
                 'roomAmenities' => array('bed'),
                 'city' => 'ASW',
                 'discount'=> '0',
             ]
        ];
 
 
        /**
         *   start API work 
         */
 
         try {

            // check for validation 

             $request->validate([
                 'city' => 'required|size:3',
                 'from' => 'required|date||after:yesterday',
                 'To' => 'required|date|after:fromDate',
                 'adultsCount' => 'required|integer',
             ]);
 
            // get matched Hotels depend of city sent by the user in the API
            // Should be SQL statemnt or ORM query if we work with DB
             $matchedHotelsByCity = array_keys(array_column($hotelsSourceData, 'city'), strtoupper($request->city));
             $matchedHotelsByCityCount = count($matchedHotelsByCity);
 
             $Top_hotels = array();
 
            // loop on the matched hotels to add thiem to the result array
             for($i=0 ; $i<$matchedHotelsByCityCount; $i++){
 
                 array_push($Top_hotels,
                         array(
                             'hotelName' => $hotelsSourceData[$matchedHotelsByCity[$i]]['hotel'],
                             'rate' => $hotelsSourceData[$matchedHotelsByCity[$i]]['hotelRate'],
                             'price' => floatVal($hotelsSourceData[$matchedHotelsByCity[$i]]['hotelFarePerDay']),  
                             'discount' => $hotelsSourceData[$matchedHotelsByCity[$i]]['discount'],
                             'amenities' => $hotelsSourceData[$matchedHotelsByCity[$i]]['roomAmenities'],
                         )
                 );
             }
 
            // return the array
             return $Top_hotels;
         }
         catch (ValidationException $e) {
             return ($e->errors());
         }  

    }




    /**
     * Helper private function
    */

    // get the total fare for the hotel depend on the number of days and number of Adults.
    function calculate_hotel_total_fare($toDate,$fromDate,$farePerDay,$numberOfAdults)
    {

        $toDate = Carbon::createFromFormat('Y-m-d', $toDate);
        $fromDate = Carbon::createFromFormat('Y-m-d', $fromDate);
        $numberOfDays =  $toDate->diffInDays($fromDate);

        return  $farePerDay * $numberOfDays * $numberOfAdults; 


    }

    // get the fare per day of a hotel depend on the number of days and number of Adults.
    function calculate_hotel_fare_per_day($toDate,$fromDate,$numberOfAdults,$totalPrice){


        $toDate = Carbon::createFromFormat('Y-m-d', $toDate);
        $fromDate = Carbon::createFromFormat('Y-m-d', $fromDate);
        $numberOfDays =  $toDate->diffInDays($fromDate);

        return $totalPrice / ( $numberOfDays * $numberOfAdults);
    }
}