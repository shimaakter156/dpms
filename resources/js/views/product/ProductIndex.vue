<template>
  <div class="container-fluid" id="productDetails">
    <breadcrumb :options="['ProductDetails']">
      <button class="btn btn-primary" @click="addItemModal()">Add Product</button>
    </breadcrumb>
    <div class="row" style="padding:8px 0px;">
      <div class="col-md-4">
        <button type="button" class="btn btn-success btn-sm" @click="exportData">Export to Excel</button>
      </div>
    </div>
    <advanced-datatable :options="tableOptions">
      <template slot="status" slot-scope="row">
        <span v-if="row.item.status='Y'" class="badge badge-success"> Active</span>
        <span v-else class="badge badge-secondary"> Inactive</span>
      </template>
      <template slot="action" slot-scope="row">
        <a href="javascript:" @click="addItemModal(row.item)"> <i class="ti-pencil-alt"></i></a>
      </template>
    </advanced-datatable>
    <add-edit-product-modal @changeStatus="changeStatus" v-if="loading"/>
  </div>
</template>
<script >

import {bus} from "../../app";
import {Common} from "../../mixins/common";
import moment from "moment";
import AddEditProductModal from "./AddEditProductModal.vue";

export default {
  components: {AddEditProductModal},
  mixins: [Common],
  data() {
    return {
      tableOptions: {
        source: 'product/list',
        search: true,
        slots: [3,4],
        hideColumn: ['Status'],
        slotsName: ['status','action'],
        sortable: [2],

        pages: [20, 50, 100],
        addHeader: ['Status','Action']
      },
      filters:{},
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
    addItemModal(row = '') {
      this.loading = true;
      setTimeout(() => {
        bus.$emit('add-edit-product', row);
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
      bus.$emit('export-data','product-list-'+moment().format('YYYY-MM-DD'))
    }
  }
}
</script>

<style>
#users table tr td:nth-child(1),table tr td:nth-child(2),table tr td:nth-child(3),table tr td:nth-child(4),table tr td:nth-child(5),table tr td:nth-child(6),table tr td:nth-child(7) {
  text-align: left;
}
</style>
