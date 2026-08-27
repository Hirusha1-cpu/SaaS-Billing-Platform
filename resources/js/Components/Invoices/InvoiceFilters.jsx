import React, { useState } from 'react';
import { Input } from '../Common/Input';
import { Select } from '../Common/Select';
import { Button } from '../Common/Button';

const statusOptions = [
  { value: '', label: 'All Status' },
  { value: 'draft', label: 'Draft' },
  { value: 'sent', label: 'Sent' },
  { value: 'partially_paid', label: 'Partially Paid' },
  { value: 'paid', label: 'Paid' },
  { value: 'overdue', label: 'Overdue' },
];

export const InvoiceFilters = ({ onFilter, onReset }) => {
  const [filters, setFilters] = useState({
    status: '',
    search: '',
    from_date: '',
    to_date: '',
  });

  const handleChange = (key, value) => {
    setFilters((prev) => ({ ...prev, [key]: value }));
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    onFilter(filters);
  };

  const handleReset = () => {
    setFilters({ status: '', search: '', from_date: '', to_date: '' });
    onReset();
  };

  return (
    <form onSubmit={handleSubmit} className="bg-white rounded-xl shadow-sm p-4 mb-6">
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Input
          placeholder="Search invoices..."
          value={filters.search}
          onChange={(e) => handleChange('search', e.target.value)}
        />
        <Select
          options={statusOptions}
          value={filters.status}
          onChange={(e) => handleChange('status', e.target.value)}
        />
        <Input
          type="date"
          value={filters.from_date}
          onChange={(e) => handleChange('from_date', e.target.value)}
        />
        <Input
          type="date"
          value={filters.to_date}
          onChange={(e) => handleChange('to_date', e.target.value)}
        />
      </div>
      <div className="flex justify-end gap-2 mt-4">
        <Button type="button" variant="outline" size="sm" onClick={handleReset}>
          Reset
        </Button>
        <Button type="submit" size="sm">
          Apply Filters
        </Button>
      </div>
    </form>
  );
};