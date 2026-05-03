import Vue from 'vue'
import VueRouter from 'vue-router'
import Login from '../views/auth/Login.vue';
import Main from '../components/layouts/Main';
import Dashboard from '../views/dashboard/Index.vue';
import Users from '../views/users/Index';
import SRInfo from '../views/setup/srList.vue';
import LocationList from '../views/setup/locationList.vue';
import ProductDetails from '../views/product/ProductIndex.vue';
import ProductMarketPrice from '../views/product/MarketPrice.vue';
import WholesaleMarketPrice from '../views/product/WholesaleMarketPrice.vue';
import DayWiseMarketRateReport from '../views/reports/MarketRate/dayWiseMarketRate.vue';
import MarketRatePivotReport from '../views/reports/MarketRate/wholesaleMarketRate.vue';



import NotFound from '../views/404/Index';
import {baseurl} from '../base_url'

Vue.use(VueRouter);

const config = () => {
    let token = localStorage.getItem('token');
    return {
        headers: {Authorization: `Bearer ${token}`}
    };
}
const checkToken = (to, from, next) => {
    let token = localStorage.getItem('token');
    if (token === 'undefined' || token === null || token === '') {
        next(baseurl + 'login');
    } else {
        next();
    }
};

const activeToken = (to, from, next) => {
    let token = localStorage.getItem('token');
    if (token === 'undefined' || token === null || token === '') {
        next();
    } else {
        next(baseurl);
    }
};

const routes = [
    {
        path: baseurl,
        component: Main,
        redirect: {name: 'Dashboard'},
        children: [
            {
                path: baseurl + 'dashboard',
                name: 'Dashboard',
                component: Dashboard
            },
            {
                path: baseurl + 'users',
                name: 'Users',
                component: Users
            },
            {
                path: baseurl + 'setup/sr-info',
                name: 'SRInfo',
                component: SRInfo
            }, {
                path: baseurl + 'setup/location-list',
                name: 'LocationList',
                component: LocationList
            },
            {
                path: baseurl + 'product/details',
                name: 'ProductDetails',
                component: ProductDetails
            },{
                path: baseurl + 'product/market-price',
                name: 'ProductMarketPrice',
                component: ProductMarketPrice
            },{
                path: baseurl + 'product/wholesale-market-rate',
                name: 'WholesaleMarketPrice',
                component: WholesaleMarketPrice
            },
             {
                path: baseurl + 'report/day-wise-market-rate',
                name: 'DayWiseMarketRateReport',
                component: DayWiseMarketRateReport
            }, {
                path: baseurl + 'report/wholesale-market-rate',
                name: 'MarketRatePivotReport',
                component: MarketRatePivotReport
            },


        ],
        beforeEnter(to, from, next) {
            checkToken(to, from, next);
        }
    },
    {
        path: baseurl + 'login',
        name: 'Login',
        component: Login,
        beforeEnter(to, from, next) {
            activeToken(to, from, next);
        }
    },
    {
        path: baseurl + '*',
        name: 'NotFound',
        component: NotFound,
    },
]

const router = new VueRouter({
    mode: 'history',
    base: process.env.baseurl,
    routes
});

router.afterEach(() => {
    $('#preloader').hide();
});

export default router
