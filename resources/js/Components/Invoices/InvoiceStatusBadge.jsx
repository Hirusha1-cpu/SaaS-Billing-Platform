import React from 'react';
import { Badge } from '../Common/Badge';

const statusMap = {
  draft: { variant: 'draft', label: 'Draft' },
  sent: { variant: 'sent', label: 'Sent' },
  partially_paid: { variant: 'partially_paid', label: 'Partially Paid' },
  paid: { variant: 'paid', label: 'Paid' },
  overdue: { variant: 'overdue', label: 'Overdue' },
  cancelled: { variant: 'cancelled', label: 'Cancelled' },
  refunded: { variant: 'refunded', label: 'Refunded' },
};

export const InvoiceStatusBadge = ({ status }) => {
  const config = statusMap[status] || { variant: 'draft', label: status };
  return <Badge variant={config.variant}>{config.label}</Badge>;
};