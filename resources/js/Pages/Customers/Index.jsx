import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { Card } from '../../Components/Common/Card';
import { Button } from '../../Components/Common/Button';
import { Spinner } from '../../Components/Common/Spinner';
import { Input } from '../../Components/Common/Input';
import api from '../../Utils/api';
import { formatCurrency } from '../../Utils/helpers';
import toast from 'react-hot-toast';

const CustomerIndex = () => {
  const navigate = useNavigate();
  const [customers, setCustomers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [pagination, setPagination] = useState(null);

  useEffect(() => {
    fetchCustomers();
  }, []);

  const fetchCustomers = async (params = {}) => {
    setLoading(true);
    try {
      const response = await api.get('/customers', { params });
      setCustomers(response.data.data);
      setPagination(response.data);
    } catch (error) {
      toast.error('Failed to fetch customers');
    } finally {
      setLoading(false);
    }
  };

  const handleSearch = (e) => {
    e.preventDefault();
    fetchCustomers({ search });
  };

  const handleReset = () => {
    setSearch('');
    fetchCustomers();
  };

  const handleDelete = async (id) => {
    if (!confirm('Are you sure you want to delete this customer?')) return;
    try {
      await api.delete(`/customers/${id}`);
      toast.success('Customer deleted successfully');
      fetchCustomers();
    } catch (error) {
      toast.error(error.response?.data?.error || 'Failed to delete customer');
    }
  };

  return (
    <div>
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-2xl font-bold">Customers</h1>
        <Button onClick={() => navigate('/customers/create')}>
          + New Customer
        </Button>
      </div>

      {/* Search */}
      <form onSubmit={handleSearch} className="flex gap-2 mb-6">
        <Input
          placeholder="Search customers..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          className="flex-1"
        />
        <Button type="submit">Search</Button>
        <Button type="button" variant="outline" onClick={handleReset}>
          Reset
        </Button>
      </form>

      {/* Customers Table */}
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
                    <th className="px-4 py-3 text-left text-sm font-medium text-gray-500">Name</th>
                    <th className="px-4 py-3 text-left text-sm font-medium text-gray-500">Email</th>
                    <th className="px-4 py-3 text-left text-sm font-medium text-gray-500">Company</th>
                    <th className="px-4 py-3 text-right text-sm font-medium text-gray-500">Total Paid</th>
                    <th className="px-4 py-3 text-right text-sm font-medium text-gray-500">Balance</th>
                    <th className="px-4 py-3 text-center text-sm font-medium text-gray-500">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {customers.length === 0 ? (
                    <tr>
                      <td colSpan="6" className="px-4 py-8 text-center text-gray-500">
                        No customers found
                      </td>
                    </tr>
                  ) : (
                    customers.map((customer) => (
                      <tr key={customer.id} className="border-t border-gray-100 hover:bg-gray-50">
                        <td className="px-4 py-3 text-sm font-medium text-gray-900">
                          {customer.name}
                        </td>
                        <td className="px-4 py-3 text-sm text-gray-600">
                          {customer.email || '-'}
                        </td>
                        <td className="px-4 py-3 text-sm text-gray-600">
                          {customer.company_name || '-'}
                        </td>
                        <td className="px-4 py-3 text-sm text-right">
                          {formatCurrency(customer.total_paid || 0)}
                        </td>
                        <td className="px-4 py-3 text-sm text-right font-semibold text-red-600">
                          {formatCurrency(customer.outstanding_balance || 0)}
                        </td>
                        <td className="px-4 py-3 text-center">
                          <div className="flex justify-center gap-2">
                            <Button
                              size="sm"
                              variant="outline"
                              onClick={() => navigate(`/customers/${customer.id}`)}
                            >
                              View
                            </Button>
                            <Button
                              size="sm"
                              variant="outline"
                              onClick={() => navigate(`/customers/${customer.id}/edit`)}
                            >
                              Edit
                            </Button>
                            <Button
                              size="sm"
                              variant="danger"
                              onClick={() => handleDelete(customer.id)}
                            >
                              Delete
                            </Button>
                          </div>
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
                    onClick={() => fetchCustomers({ search, page: pagination.current_page - 1 })}
                  >
                    Previous
                  </Button>
                  <Button
                    variant="outline"
                    size="sm"
                    disabled={!pagination.next_page_url}
                    onClick={() => fetchCustomers({ search, page: pagination.current_page + 1 })}
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

export default CustomerIndex;