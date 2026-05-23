import lodash from 'lodash';
import Popper from 'popper.js';
import jquery from 'jquery';
import 'bootstrap';
import axios from 'axios';

window._ = lodash;
window.Popper = Popper;
window.$ = window.jQuery = jquery;
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
