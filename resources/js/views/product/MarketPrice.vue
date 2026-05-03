<template>
  <div class="container-fluid" id="marketPrice">
    <breadcrumb :options="['MarketPrice']">
<!--      @click="addDeptModal()"-->
<!--      <button class="btn btn-primary" >Add Product</button>-->
    </breadcrumb>
    <div class="row" style="padding:8px 0px;">
      <div class="col-md-4">
        <button type="button" class="btn btn-success btn-sm" @click="exportData">Export to Excel</button>
      </div>
    </div>
    <advanced-datatable :options="tableOptions">
<!--      <template slot="action" slot-scope="row">-->
<!--&lt;!&ndash;        @click="addDeptModal(row.item)"&ndash;&gt;-->
<!--        <a href="javascript:" > <i class="ti-pencil-alt"></i></a>-->
<!--      </template>-->
    </advanced-datatable>
    <add-edit-user @changeStatus="changeStatus" v-if="loading"/>
  </div>
</template>
<script >

import {bus} from "../../app";
import {Common} from "../../mixins/common";
import moment from "moment";

export default {
  mixins: [Common],
  data() {
    return {
      tableOptions: {
        source: 'product/market-price-list',
        search: true,
        slots: [5],
        hideColumn: ['Status'],
        // slotsName: ['action'],
        sortable: [2],
        pages: [20, 50, 100],
        // addHeader: ['Action'],
        filters: [
          {
            type:'rangepicker',
            title: 'Select Date Range',
            value: moment().format('yyyy-MM-DD')
          }
        ],
      },

      loading: false,
      cpLoading: false
    }
  },
  mounted() {
    bus.$off('changeStatus',function () {
      this.changeStatus()
    })
  },
  methods: {
    changeStatus() {
      this.loading = false
    },
    addDeptModal(row = '') {
      this.loading = true;
      setTimeout(() => {
        bus.$emit('add-edit-user', row);
      })
    },
    changePassword(row) {
      this.loading = true;
      setTimeout(() => {
        bus.$emit('edit-password', row);
      })
    },
    deleteDept(id) {
      this.deleteAlert(() => {
        this.axiosDelete('users', id, (response) => {
          this.successNoti(response.message);
          this.$store.commit('departmentDelete', id);
          bus.$emit('refresh-datatable');
        }, (error) => {
          this.errorNoti(error);
        })
      });
    },
    exportData() {
      bus.$emit('export-data','market-price-'+moment().format('YYYY-MM-DD'))
    }
  }
}
</script>

<style>
#users table tr td:nth-child(1),table tr td:nth-child(2),table tr td:nth-child(3),table tr td:nth-child(4),table tr td:nth-child(5),table tr td:nth-child(6),table tr td:nth-child(7) {
  text-align: left;
}
</style>
