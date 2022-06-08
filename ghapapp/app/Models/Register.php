<?php

namespace TLCMap\Models;

class Register extends \Eloquent
{
    protected $table = "register";
    protected $primaryKey = "anps_id";
    public $incrementing = "true";
    public $timestamps = false;

    protected $fillable = ['anps_id,state_id,feature_term,feature_code,latitude,longitude,description,lga_name,state_code,rego_status,related_names,discussion,other_data,
        tlcm_latitude,tlcm_longitude,tlcm_start,tlcm_end,TLCM_GDA94_Latitude_Decimal,TLCM_GDA94_Longitude_Decimal,GNR_AGD66_Latitude,GNR_AGD66_Longitude,GNR_GDA94_Latitude,GNR_GDA94_Longitude,
            PRIMARY_DATA_SOURCE,flag,google_maps_link,original_data_source,source_link'];
    protected $sortable = ['anps_id,state_id,feature_term,feature_code,latitude,longitude,description,lga_name,state_code,rego_status,related_names,discussion,other_data,
        tlcm_latitude,tlcm_longitude,tlcm_start,tlcm_end,TLCM_GDA94_Latitude_Decimal,TLCM_GDA94_Longitude_Decimal,GNR_AGD66_Latitude,GNR_AGD66_Longitude,GNR_GDA94_Latitude,GNR_GDA94_Longitude,
            PRIMARY_DATA_SOURCE,flag,google_maps_link,original_data_source,source_link'];
    
}
