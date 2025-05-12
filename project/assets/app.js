/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */

import './styles/variables.css';
import './externals/bootstrap/css/bootstrap.min.css';
import './externals/bootstrap-icons/bootstrap-icons.css';

import './externals/swiper/swiper-bundle.min.css';

import './externals/bootstrap/js/bootstrap.bundle.min.js';
import './externals/isotope-layout/isotope.pkgd.min.js';
import './externals/php-email-form/validate.js';

import $ from 'jquery';
global.$ = global.jQuery = $;


import 'datatables.net-bs5';
import 'select2';
import 'toastr';
// import 'datatables.net-dt/css/jquery.dataTables.min.css'; 
import './js/main.js';
import './js/table.js';
import './js/upload.js'
import './js/button.js';

import './styles/main.css';


console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');
