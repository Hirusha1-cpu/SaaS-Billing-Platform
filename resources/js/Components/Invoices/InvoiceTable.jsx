import React from 'react';
import { Table } from '../Common/Table';
import { InvoiceStatusBadge } from './InvoiceStatusBadge';
import { formatCurrency, formatDate } from '../../Utils/helpers';

export const InvoiceTable = ({ invoices, onRowClick }) => {
  const columns = [
    { key: 'invoice_number', header: 'Invoice #', width: '15%' },
    {
      key: 'customer',
      header: 'Customer',
      width: '20%',
      render: (_, row) => row.customer?.name || '-',
    },
    {
      key: 'issue_date',
      header: 'Date',
      width: '15%',
      render: (val) => formatDate(val),
    },
    {
      key: 'total',
      header: 'Total',
      width: '15%',
      render: (val, row) => formatCurrency(val, row.currency),
    },
    {
      key: 'status',
      header: 'Status',
      width: '15%',
      render: (val) => <InvoiceStatusBadge status={val} />,
    },
    {
      key: 'balance_due',
      header: 'Balance Due',
      width: '20%',
      render: (val, row) => formatCurrency(val, row.currency),
    },
  ];

  return <Table columns={columns} data={invoices} onRowClick={onRowClick} />;
};