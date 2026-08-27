import React from 'react';
import { cva } from 'class-variance-authority';
import { cn } from '../../Utils/helpers';

const badgeVariants = cva(
  'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
  {
    variants: {
      variant: {
        draft: 'bg-gray-100 text-gray-800',
        sent: 'bg-blue-100 text-blue-800',
        partially_paid: 'bg-yellow-100 text-yellow-800',
        paid: 'bg-green-100 text-green-800',
        overdue: 'bg-red-100 text-red-800',
        cancelled: 'bg-red-100 text-red-800',
        refunded: 'bg-purple-100 text-purple-800',
        active: 'bg-green-100 text-green-800',
        inactive: 'bg-gray-100 text-gray-800',
        pending: 'bg-yellow-100 text-yellow-800',
      },
    },
  }
);

export const Badge = ({ variant, children, className }) => {
  return (
    <span className={cn(badgeVariants({ variant }), className)}>
      {children}
    </span>
  );
};