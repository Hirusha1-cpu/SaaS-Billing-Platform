import React from 'react';
import { cn } from '../../Utils/helpers';

export const Card = ({ children, className, title, actions }) => {
  return (
    <div className={cn('bg-white rounded-xl shadow-sm p-6', className)}>
      {(title || actions) && (
        <div className="flex justify-between items-center mb-4">
          {title && <h3 className="text-lg font-semibold">{title}</h3>}
          {actions && <div className="flex gap-2">{actions}</div>}
        </div>
      )}
      {children}
    </div>
  );
};