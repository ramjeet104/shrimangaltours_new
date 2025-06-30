
<script src="<?= site_url() ?>v3_epoly.js"></script>

<script>
    var site_url = "<?= site_url() ?>";
    var view_url = "<?= (isset($b_id) && $b_id != '')?encryptUrl('view/' . $b_id):'';?>";
    var booking_url = "booking_f/bform_outstation/";
    var vehicles = [];
    var v_cat = [];
<?php foreach ($vcategories as $vcat) { ?>
        vehicles[<?= $vcat->id ?>] = [];
        v_cat.push({
            val: '<?= $vcat->id ?>',
            name: '<?= $vcat->name ?>'
        });
<?php } ?>
<?php foreach ($vehicles as $vehicle) { ?>
        vehicles[<?= $vehicle->vcat_id ?>].push({'id': '<?= $vehicle->id ?>', 'text': '<?= $vehicle->number . ' | ' . $vehicle->name ?>'});
<?php } ?>

    $(document).ready(function () {
        setTimeout(function () {
            $('.sidebar-toggle').click();
        }, 300);

    });

    var app = angular.module('myApp', ['ngAnimate']);
    app.run(function ($rootScope) {
        //$rootScope.color = 'blue';booking_status
        $rootScope.sectionsClass = '';
        
        $rootScope.autoNext = true;
        
        $rootScope.rootOverlay = true;
        
        $rootScope.sections = {
            client : false,
            packages : false,
            booking : false,
            itinerary : false,
            states : false,
            fare : false,
            vehicle : false,
            quotation : false,
            payment : false,
            collection : false,
            amenities : false,
            instruction : false,
            send : false
        };
        
        $rootScope.goto = function (str,autoNext=true){
            $rootScope.sectionsClass = str;
            
            $rootScope.autoNext = autoNext;
            
            if(str === 'client'){
                $rootScope.$broadcast('broadcast:client_detail', 'open');
            }else if(str === 'packages'){
                $rootScope.$broadcast('broadcast:packages_detail', 'open');
            }else if(str === 'booking'){
                $rootScope.$broadcast('broadcast:booking_detail', 'open');
            }else if(str === 'itinerary'){
                $rootScope.$broadcast('broadcast:itinerary_detail', 'open');
            }else if(str === 'states'){
                $rootScope.$broadcast('broadcast:states_detail', 'open');
            }else if(str === 'fare'){
                $rootScope.$broadcast('broadcast:fare_detail', 'open');
            }else if(str === 'vehicle'){
                $rootScope.$broadcast('broadcast:vehicle_detail', 'open');
            }else if(str === 'quotation'){
                $rootScope.$broadcast('broadcast:quotation_detail', 'open');
            }else if(str === 'payment'){
                $rootScope.$broadcast('broadcast:payment_detail', 'open');
            }else if(str === 'amenities'){
                $rootScope.$broadcast('broadcast:amenities_details', 'open');
            }else if(str === 'collection'){
                $rootScope.$broadcast('broadcast:collection_details', 'open');
            }else if(str === 'instruction'){
                $rootScope.$broadcast('broadcast:instruction_details', 'open');
            }else if(str === 'send'){
                $rootScope.$broadcast('broadcast:send_details', 'open');
            }
        };
        
        setTimeout(function(){ 
            $rootScope.goto('client');
            $rootScope.rootOverlay = false;
        }, 1000);
        
        $rootScope.itineraryHasChange = false;
        $rootScope.vehiclesHasChange = false;
        
        $rootScope.fareObj = {};
        $rootScope.totalAmount = 0;
        $rootScope.amenities = [];
        
        $rootScope.cusCredit = {};
        
        //////////////////////////
        $rootScope.booking_id = "<?= (isset($b_id) && $b_id != '') ? $b_id : ''; ?>";
        $rootScope.booking_status = "<?= (isset($booking_status) && $booking_status != '') ? $booking_status : 'draft'; ?>";
        $rootScope.booking_payment_type = 'normal';
        $rootScope.booking_fare_type = 'fixed';
        $rootScope.booking_pick_date = '';
        $rootScope.booking_drop_date = '';
        $rootScope.executive_id = '<?= $this->session->userid ?>';
        $rootScope.executive_type = '<?= $this->session->type ?>';
        $rootScope.booking_gst = '0';
        $rootScope.booking_gst_rate = <?= getSettingVal('igst') ? booking_gst_rate : '0'; ?>;
        $rootScope.siteFunction = function () {
            $.validate({
                modules: 'html5,location, date, security, file, logic',
                onError: function () {
                    $('.input-group.has-error').find('.help-block.form-error').each(function () {
                        $(this).closest('.form-group').addClass('has-error').append($(this));
                    });
                }
            });

            //Flat red color scheme for iCheck
            /* $('input[type="checkbox"].flat-red, input[type="radio"].flat-red').iCheck({
             checkboxClass: 'icheckbox_flat-green',
             radioClass: 'iradio_flat-green'
             }); */

            //Timepicker
            $('.timepicker').timepicker({
                //showInputs: false
            });
            //Initialize Select2 Elements
            $('.select2').select2();

            /* $('input').on('ifChanged', function (event) { 
             angular.element(event.target).trigger('click');
             //angular.element(event.target).trigger('change');
             }); */


        };
        $rootScope.siteFunction();
    });
</script>

<div ng-app="myApp">

    <div class="col-md-3">
        <div class="box box-solid">
            <div class="box-header with-border">
                <h3 class="box-title">Sections</h3>
            </div>
            <div class="box-body no-padding">
                <ul class="nav nav-pills nav-stacked">
                    <li class="" ng-class="{'active': sectionsClass == 'client'}">
                        <a href="javascript:void(0);" ng-click="goto('client',false);">
                            <i class="fa fa-user"></i> Client
                            <span ng-show="sections.client" class="label label-success pull-right"><i class="fa fa-check"></i></span>
                        </a>
                    </li>
                    <li ng-hide="booking_status != 'confirm' && booking_status != 'advance_pending' && booking_status != 'draft'" ng-class="{'active': sectionsClass == 'packages'}">
                        <a href="javascript:void(0);" ng-click="goto('packages',false);">
                            <i class="fa fa-suitcase"></i> Packages Info
                            <span ng-show="sections.packages" class="label label-success pull-right"><i class="fa fa-check"></i></span>
                        </a>
                    </li>
                    <li ng-class="{'active': sectionsClass == 'booking'}">
                        <a href="javascript:void(0);" ng-click="goto('booking',false);">
                            <i class="fa fa-newspaper-o"></i> Booking Info
                            <span ng-show="sections.booking" class="label label-success pull-right"><i class="fa fa-check"></i></span>
                        </a>
                    </li>
                    <li ng-class="{'active': sectionsClass == 'itinerary'}">
                        <a href="javascript:void(0);" ng-click="goto('itinerary',false);">
                            <i class="fa fa-road"></i> Itinerary Detail
                            <span ng-show="sections.itinerary" class="label label-success pull-right"><i class="fa fa-check"></i></span>
                        </a>
                    </li>
                    <?php /*<li ng-class="{'active': sectionsClass == 'states'}">
                        <a href="javascript:void(0);" ng-click="goto('states',false);">
                            <i class="fa fa-map-marker"></i> Fetch km and states (Auto)
                            <span ng-show="sections.states" class="label label-success pull-right"><i class="fa fa-check"></i></span>
                        </a>
                    </li>*/ ?>
                    <li ng-class="{'active': sectionsClass == 'fare'}">
                        <a href="javascript:void(0);" ng-click="goto('fare',false);">
                            <i class="fa fa-info-circle"></i> Fare Detail 
                            <span ng-show="sections.fare" class="label label-success pull-right"><i class="fa fa-check"></i></span>
                        </a>
                    </li>
                    <li ng-class="{'active': sectionsClass == 'vehicle'}">
                        <a href="javascript:void(0);" ng-click="goto('vehicle',false);">
                            <i class="fa fa-cab"></i> Vehicles Detail 
                            <span ng-show="sections.vehicle" class="label label-success pull-right"><i class="fa fa-check"></i></span>
                        </a>
                    </li>
                    <li ng-class="{'active': sectionsClass == 'quotation'}">
                        <a href="javascript:void(0);" ng-click="goto('quotation',false);">
                            <i class="fa fa-file-text"></i> Quotation Detail
                            <span ng-show="sections.quotation" class="label label-success pull-right"><i class="fa fa-check"></i></span>
                        </a>
                    </li>
                    <li ng-class="{'active': sectionsClass == 'payment'}">
                        <a href="javascript:void(0);" ng-click="goto('payment',false);">
                            <i class="fa fa-rupee"></i> Payment Detail 
                            <span ng-show="sections.payment" class="label label-success pull-right"><i class="fa fa-check"></i></span>
                        </a>
                    </li>
                    <li ng-class="{'active': sectionsClass == 'amenities'}">
                        <a href="javascript:void(0);" ng-click="goto('amenities',false);">
                            <i class="fa fa-briefcase"></i> Amenities 
                            <span ng-show="sections.amenities" class="label label-success pull-right"><i class="fa fa-check"></i></span>
                        </a>
                    </li>
                    <li ng-class="{'active': sectionsClass == 'collection'}">
                        <a href="javascript:void(0);" ng-click="goto('collection',false);">
                            <i class="fas fan-hand-holding-usd"></i> Payment to be collected 
                            <span ng-show="sections.collection" class="label label-success pull-right"><i class="fa fa-check"></i></span>
                        </a>
                    </li>
                    <li ng-class="{'active': sectionsClass == 'instruction'}">
                        <a href="javascript:void(0);" ng-click="goto('instruction',false);">
                            <i class="fa fa-reorder"></i> Other Instruction  
                            <span ng-show="sections.instruction" class="label label-success pull-right"><i class="fa fa-check"></i></span>
                        </a>
                    </li>
                    <li ng-class="{'active': sectionsClass == 'send'}">
                        <a href="javascript:void(0);" ng-click="goto('send',false);">
                            <i class="fa fa-send-o"></i> Booking status & send details 
                            <span ng-show="sections.send" class="label label-success pull-right"><i class="fa fa-check"></i></span>
                        </a>
                    </li>
                </ul>
            </div>
            
            <div class="overlay" ng-show="rootOverlay">
                <i class="fa fa-refresh fa-spin"></i>
            </div>
            <!-- /.box-body -->
        </div>
        <!-- /. box -->
    </div>
    <div class="col-md-9">
        <?php include __DIR__ . '/client_details.html'; ?>
        <?php include __DIR__ . '/packages_details.html'; ?>
        <?php include __DIR__ . '/booking_details.html'; ?>
        <?php include __DIR__ . '/itinerary_details.html'; ?>
        <?php include __DIR__ . '/states_details.html'; ?>
        <?php include __DIR__ . '/fare_details.html'; ?>
        <?php include __DIR__ . '/vehicle_details.html'; ?>
        <?php include __DIR__ . '/quotation_details.html'; ?>
        <?php include __DIR__ . '/payment_details.html'; ?>
        <?php include __DIR__ . '/amenities_details.html'; ?>
        <?php include __DIR__ . '/collection_details.html'; ?>
        <?php include __DIR__ . '/other_instructions.html'; ?>
        <?php include __DIR__ . '/sms_and_email.html'; ?>
    </div>
</div>