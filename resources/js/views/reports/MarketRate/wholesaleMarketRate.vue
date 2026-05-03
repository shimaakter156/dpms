<template>
  <div class="container-fluid">
    <breadcrumb :options="['MarketRatePivotReport']" />
    <div class="card">
      <div class="card-header">
        <span>Market Rate Report</span>
      </div>
      <div class="card-body">

        <div class="row mb-2">
          <div class="col-md-3">
            <label>Date {{isLoading}}</label>
            <input type="date" class="form-control" v-model="selectedDate" @change="fetchReport" />
          </div>
          <div class="col-md-3" style="display:flex;align-items:flex-end;gap:5px;">
            <button class="btn btn-success btn-sm" @click="exportExcel">Export Excel</button>
          </div>
        </div>

        <div v-if="isLoading" class="text-center py-4">
          <skeleton-loader :row="4" />
        </div>

        <div v-else-if="products.length > 0" style="overflow-x:auto;">
          <table class="table table-bordered table-sm rate-table">
            <thead>
            <tr>
              <td class="hdr-location" colspan="2">Date: {{ formattedDate }}</td>
              <td v-for="loc in locations" :key="loc.LocationCode" colspan="3">{{loc.LocationName}}</td>
            </tr>
            <tr class="hdr-location">
              <th>Product</th>
              <th>Company Rate</th>
              <template v-for="loc in locations">
                <th >Market Rate</th>
                <th >Diff</th>
                <th >% of U.Rate</th>
              </template>
            </tr>
            </thead>
            <tbody>
            <tr v-for="(product, idx) in products" :key="idx">
              <td class="td-product">{{ product.ProductName }}</td>
              <td class="td-num td-company">{{ parseFloat(product.CompanyRate).toFixed(2) }}</td>

              <template v-for="loc in locations">
                <template v-if="product.Locations[loc.LocationCode]?.MarketRate">
                  <td class="td-num td-mrate">
                    {{ parseFloat(product.Locations[loc.LocationCode].MarketRate).toFixed(2) }}
                  </td>
                  <td class="td-num" :class="parseFloat(product.Locations[loc.LocationCode].Diff) < 0 ? 'td-neg' : ''">
                    {{ parseFloat(product.Locations[loc.LocationCode].Diff).toFixed(2) }}
                  </td>
                  <td class="td-num">
                    {{ parseFloat(product.Locations[loc.LocationCode].PercentURate).toFixed(2) }}
                  </td>
                </template>
                <template v-else>
                  <td colspan="3" class="text-center text-muted" style="color:#bbb;">-</td>
                </template>
              </template>
            </tr>
            </tbody>
          </table>
        </div>

        <div v-else class="text-center py-4 text-muted">
          No data found for {{ formattedDate }}.
        </div>

      </div>
    </div>
  </div>
</template>

<script>
import { Common } from "../../../mixins/common";
import moment from "moment";

const XLSX = require('xlsx-js-style');

export default {
  mixins: [Common],
  data() {
    return {
      isLoading: false,
      selectedDate: new Date().toISOString().split('T')[0],
      locations: [],
      products: []
    };
  },
  computed: {
    formattedDate() {
      return moment(this.selectedDate).format('DD-MMM-YYYY');
    }
  },
  mounted() {
    this.fetchReport();
  },
  methods: {
    fetchReport() {
      this.isLoading = true;
      this.axiosGet('report/market-rate-pivot?date=' + this.selectedDate, function(response) {
          this.locations = response.locations;
          this.products  = response.products;
          this.isLoading = false;

      }.bind(this), function(error) {
        console.error(error);
        this.isLoading = false;
      }.bind(this));
    },

    exportExcel() {
      var self      = this;
      var locations = this.locations;
      var products  = this.products;

      var mkCell = function(value, bg, color, bold, align) {
        bg    = bg    || 'FFFFFF';
        color = color || '000000';
        bold  = bold  || false;
        align = align || 'left';
        var isNum = typeof value === 'number';
        return {
          v: value, t: isNum ? 'n' : 's',
          s: {
            fill:      { fgColor: { rgb: bg } },
            font:      { name: 'Arial', sz: 9, bold: bold, color: { rgb: color } },
            alignment: { horizontal: align, vertical: 'center' },
            border: {
              top:    { style: 'thin', color: { rgb: 'CCCCCC' } },
              bottom: { style: 'thin', color: { rgb: 'CCCCCC' } },
              left:   { style: 'thin', color: { rgb: 'CCCCCC' } },
              right:  { style: 'thin', color: { rgb: 'CCCCCC' } }
            }
          }
        };
      };

      var rows = [];

      // Row 1: Date + location names
      var r1 = [
        mkCell('Date: ' + self.formattedDate, 'FFD700', '000000', true),
        mkCell('', 'FFD700'), mkCell('', 'FFD700')
      ];
      locations.forEach(function(loc) {
        r1.push(mkCell(loc.LocationName, '1A3C6E', 'FFFFFF', true, 'center'));
        r1.push(mkCell('', '1A3C6E'));
        r1.push(mkCell('', '1A3C6E'));
      });
      rows.push(r1);

      // Row 2: Column headers
      var r2 = [
        mkCell('Product',      'FFD700', '000000', true),
        mkCell('Company Rate', 'FFD700', '000000', true, 'right'),
        mkCell('',             'FFD700')
      ];
      locations.forEach(function() {
        r2.push(mkCell('Market Rate', 'FFD700', '000000', true, 'right'));
        r2.push(mkCell('Diff',        'FFD700', '000000', true, 'right'));
        r2.push(mkCell('% of U.Rate', 'FFD700', '000000', true, 'right'));
      });
      rows.push(r2);

      // Product rows
      products.forEach(function(product, idx) {
        var bg  = idx % 2 === 0 ? 'FFFFFF' : 'F7F7F7';
        var row = [
          mkCell(product.ProductName,                  bg, '000000', false, 'left'),
          mkCell(parseFloat(product.CompanyRate),      bg, '000000', false, 'right'),
          mkCell('', bg)
        ];
        locations.forEach(function(loc) {
          var d = product.Locations[loc.LocationCode];
          if (d) {
            var diff = parseFloat(d.Diff);
            row.push(mkCell(parseFloat(d.MarketRate),   bg, '008000', true,  'right'));
            row.push(mkCell(parseFloat(d.Diff),         bg, diff < 0 ? 'CC0000' : '000000', false, 'right'));
            row.push(mkCell(parseFloat(d.PercentURate), bg, '000000', false, 'right'));
          } else {
            row.push(mkCell('', bg)); row.push(mkCell('', bg)); row.push(mkCell('', bg));
          }
        });
        rows.push(row);
      });

      var ws = XLSX.utils.aoa_to_sheet(rows);

      // Merge location header cells
      ws['!merges'] = [];
      // Merge date cell (cols 0-2)
      ws['!merges'].push({ s: { r: 0, c: 0 }, e: { r: 0, c: 2 } });
      // Merge each location header (3 cols each)
      locations.forEach(function(_, i) {
        var start = 3 + (i * 3);
        ws['!merges'].push({ s: { r: 0, c: start }, e: { r: 0, c: start + 2 } });
      });

      // Column widths
      ws['!cols'] = [{ wch: 22 }, { wch: 12 }, { wch: 2 }];
      locations.forEach(function() {
        ws['!cols'].push({ wch: 10 });
        ws['!cols'].push({ wch: 8  });
        ws['!cols'].push({ wch: 10 });
      });

      var wb = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(wb, ws, 'Market Rate');
      XLSX.writeFile(wb, 'market-rate-pivot-' + self.selectedDate + '.xlsx');
    }
  }
};
</script>

<style scoped>
.rate-table td, .rate-table th { font-size: 11px; padding: 2px 4px !important; white-space: nowrap; }
.hdr-date     { background: #FFD700; font-weight: bold; }
.hdr-location { background: #1A3C6E; color: #fff; font-weight: bold; text-align: center; }
.thead-yellow th { background: #FFD700; font-weight: bold; text-align: right; }
.thead-yellow th:first-child { text-align: left; }
.td-product   { text-align: left; min-width: 130px; }
.td-num       { text-align: right; }
.td-company   { background: #FFFACD; font-weight: bold; }
.td-mrate     { color: #008000; font-weight: bold; }
.td-neg       { color: #cc0000; }
</style>