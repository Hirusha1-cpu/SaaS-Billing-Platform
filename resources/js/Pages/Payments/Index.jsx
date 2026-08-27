import React, { useState, useEffect } from 'react';
import { Card } from '../../Components/Common/Card';
import { Button } from '../../Components/Common/Button';
import { Spinner } from '../../Components/Common/Spinner';
import { Input } from '../../Components/Common/Input';
import { Select } from '../../Components/Common/Select';
import api from '../../Utils/api';
import { formatCurrency, formatDate } from '../../Utils/helpers';
import toast from 'react-hot-toast';

const PaymentIndex = () => {
  const [payments, setPayments] = useState([]);
  const [loading, setLoading] = useState(true);
  const [filters, setFilters] = useState({
    status: '',
    from_date: '',
    to_date: '',
  });
  const [pagination, setPagination] = useState(null);

  useEffect(() => {
    fetchPayments();
  }, []);

  const fetchPayments = async (params = {}) => {
    setLoading(true);
    try {
      const response = await api.get('/payments', { params });
      setPayments(response.data.data);
      setPagination(response.data);
    } catch (error) {
      toast.error('Failed to fetch payments');
    } finally {
      setLoading(false);
    }
  };

  const handleFilter = (e) => {
    e.preventDefault();
    fetchPayments(filters);
  };

  const handleReset = () => {
    setFilters({ status: '', from_date: '', to_date: '' });
    fetchPayments();
  };

  const getStatusColor = (status) => {
    const colors = {
      pending: 'bg-yellow-100 text-yellow-800',
      processing: 'bg-blue-100 text-blue-800',
      completed: 'bg-green-100 text-green-800',
      failed: 'bg-red-100 text-red-800',
      refunded: 'bg-purple-100 text-purple-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
  };

  const getStatusLabel = (status) => {
    const labels = {
      pending: 'Pending',
      processing: 'Processing',
      completed: 'Completed',
      failed: 'Failed',
      refunded: 'Refunded',
    };
    return labels[status] || status;
  };

  return (
    <div>
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-2xl font-bold">Payments</h1>
        <div className="flex gap-2">
          <Button variant="outline" size="sm">
            Export
          </Button>
        </div>
      </div>

      {/* Filters */}
      <form onSubmit={handleFilter} className="bg-white rounded-xl shadow-sm p-4 mb-6">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <Select
            options={[
              { value: '', label: 'All Status' },
              { value: 'pending', label: 'Pending' },
              { value: 'processing', label: 'Processing' },
              { value: 'completed', label: 'Completed' },
              { value: 'failed', label: 'Failed' },
              { value: 'refunded', label: 'Refunded' },
            ]}
            value={filters.status}
            onChange={(e) => setFilters({ ...filters, status: e.target.value })}
          />
          <Input
            type="date"
            value={filters.from_date}
            onChange={(e) => setFilters({ ...filters, from_date: e.target.value })}
            placeholder="From Date"
          />
          <Input
            type="date"
            value={filters.to_date}
            onChange={(e) => setFilters({ ...filters, to_date: e.target.value })}
            placeholder="To Date"
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

      {/* Payments Table */}
      <Card>
        {loading ? (
          <div className="flex justify-center items-center py-12">
            <Spinner size="lg" />
          </div>
        ) : (
          <>
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-4 py-3 text-left text-sm font-medium text-gray-500">Payment ID</th>
                    <th className="px-4 py-3 text-left text-sm font-medium text-gray-500">Invoice</th>
                    <th className="px-4 py-3 text-left text-sm font-medium text-gray-500">Customer</th>
                    <th className="px-4 py-3 text-right text-sm font-medium text-gray-500">Amount</th>
                    <th className="px-4 py-3 text-left text-sm font-medium text-gray-500">Method</th>
                    <th className="px-4 py-3 text-left text-sm font-medium text-gray-500">Status</th>
                    <th className="px-4 py-3 text-left text-sm font-medium text-gray-500">Date</th>
                  </tr>
                </thead>
                <tbody>
                  {payments.length === 0 ? (
                    <tr>
                      <td colSpan="7" className="px-4 py-8 text-center text-gray-500">
                        No payments found
                      </td>
                    </tr>
                  ) : (
                    payments.map((payment) => (
                      <tr key={payment.id} className="border-t border-gray-100 hover:bg-gray-50">
                        <td className="px-4 py-3 text-sm text-gray-900">
                          #{payment.id}
                        </td>
                        <td className="px-4 py-3 text-sm text-blue-600">
                          {payment.invoice?.invoice_number || 'N/A'}
                        </td>
                        <td className="px-4 py-3 text-sm text-gray-900">
                          {payment.customer?.name || 'N/A'}
                        </td>
                        <td className="px-4 py-3 text-sm text-right font-semibold">
                          {formatCurrency(payment.amount, payment.currency)}
                        </td>
                        <td className="px-4 py-3 text-sm text-gray-600 capitalize">
                          {payment.payment_method}
                        </td>
                        <td className="px-4 py-3 text-sm">
                          <span className={`px-2 py-1 rounded-full text-xs font-medium ${getStatusColor(payment.status)}`}>
                            {getStatusLabel(payment.status)}
                          </span>
                        </td>
                        <td className="px-4 py-3 text-sm text-gray-500">
                          {formatDate(payment.payment_date)}
                        </td>
                      </tr>
                    ))
                  )}
                </tbody>
              </table>
            </div>

            {pagination && (
              <div className="flex justify-between items-center mt-4 pt-4 border-t border-gray-100">
                <p className="text-sm text-gray-500">
                  Showing {pagination.from || 0} to {pagination.to || 0} of {pagination.total || 0}
                </p>
                <div className="flex gap-2">
                  <Button
                    variant="outline"
                    size="sm"
                    disabled={!pagination.prev_page_url}
                    onClick={() => fetchPayments({ ...filters, page: pagination.current_page - 1 })}
                  >
                    Previous
                  </Button>
                  <Button
                    variant="outline"
                    size="sm"
                    disabled={!pagination.next_page_url}
                    onClick={() => fetchPayments({ ...filters, page: pagination.current_page + 1 })}
                  >
                    Next
                  </Button>
                </div>
              </div>
            )}
          </>
        )}
      </Card>
    </div>
  );
};

export default PaymentIndex;