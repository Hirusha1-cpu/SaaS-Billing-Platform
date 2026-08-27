import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { Card } from '../../Components/Common/Card';
import { Button } from '../../Components/Common/Button';
import { Spinner } from '../../Components/Common/Spinner';
import { Badge } from '../../Components/Common/Badge';
import { Input } from '../../Components/Common/Input';
import { Select } from '../../Components/Common/Select';
import api from '../../Utils/api';
import { formatCurrency, formatDate } from '../../Utils/helpers';
import toast from 'react-hot-toast';

const SubscriptionIndex = () => {
  const navigate = useNavigate();
  const [subscriptions, setSubscriptions] = useState([]);
  const [loading, setLoading] = useState(true);
  const [filters, setFilters] = useState({
    status: '',
    billing_cycle: '',
    search: '',
  });
  const [pagination, setPagination] = useState(null);

  useEffect(() => {
    fetchSubscriptions();
  }, []);

  const fetchSubscriptions = async (params = {}) => {
    setLoading(true);
    try {
      const response = await api.get('/subscriptions', { params });
      setSubscriptions(response.data.data);
      setPagination(response.data);
    } catch (error) {
      toast.error('Failed to fetch subscriptions');
    } finally {
      setLoading(false);
    }
  };

  const handleFilter = (e) => {
    e.preventDefault();
    fetchSubscriptions(filters);
  };

  const handleReset = () => {
    setFilters({ status: '', billing_cycle: '', search: '' });
    fetchSubscriptions();
  };

  const handlePause = async (id) => {
    try {
      await api.post(`/subscriptions/${id}/pause`);
      toast.success('Subscription paused successfully');
      fetchSubscriptions(filters);
    } catch (error) {
      toast.error('Failed to pause subscription');
    }
  };

  const handleResume = async (id) => {
    try {
      await api.post(`/subscriptions/${id}/resume`);
      toast.success('Subscription resumed successfully');
      fetchSubscriptions(filters);
    } catch (error) {
      toast.error('Failed to resume subscription');
    }
  };

  const handleCancel = async (id) => {
    if (!confirm('Are you sure you want to cancel this subscription?')) return;
    try {
      await api.delete(`/subscriptions/${id}`);
      toast.success('Subscription cancelled successfully');
      fetchSubscriptions(filters);
    } catch (error) {
      toast.error('Failed to cancel subscription');
    }
  };

  const getStatusColor = (status) => {
    const colors = {
      active: 'bg-green-100 text-green-800',
      trialing: 'bg-blue-100 text-blue-800',
      past_due: 'bg-yellow-100 text-yellow-800',
      cancelled: 'bg-red-100 text-red-800',
      paused: 'bg-gray-100 text-gray-800',
      expired: 'bg-red-100 text-red-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
  };

  const getStatusLabel = (status) => {
    const labels = {
      active: 'Active',
      trialing: 'Trialing',
      past_due: 'Past Due',
      cancelled: 'Cancelled',
      paused: 'Paused',
      expired: 'Expired',
    };
    return labels[status] || status;
  };

  const getBillingCycleLabel = (cycle) => {
    const labels = {
      daily: 'Daily',
      weekly: 'Weekly',
      monthly: 'Monthly',
      quarterly: 'Quarterly',
      yearly: 'Yearly',
    };
    return labels[cycle] || cycle;
  };

  return (
    <div>
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-2xl font-bold">Subscriptions</h1>
        <Button onClick={() => navigate('/subscriptions/create')}>
          + New Subscription
        </Button>
      </div>

      {/* Filters */}
      <form onSubmit={handleFilter} className="bg-white rounded-xl shadow-sm p-4 mb-6">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <Input
            placeholder="Search subscriptions..."
            value={filters.search}
            onChange={(e) => setFilters({ ...filters, search: e.target.value })}
          />
          <Select
            options={[
              { value: '', label: 'All Status' },
              { value: 'active', label: 'Active' },
              { value: 'trialing', label: 'Trialing' },
              { value: 'past_due', label: 'Past Due' },
              { value: 'cancelled', label: 'Cancelled' },
              { value: 'paused', label: 'Paused' },
              { value: 'expired', label: 'Expired' },
            ]}
            value={filters.status}
            onChange={(e) => setFilters({ ...filters, status: e.target.value })}
          />
          <Select
            options={[
              { value: '', label: 'All Cycles' },
              { value: 'daily', label: 'Daily' },
              { value: 'weekly', label: 'Weekly' },
              { value: 'monthly', label: 'Monthly' },
              { value: 'quarterly', label: 'Quarterly' },
              { value: 'yearly', label: 'Yearly' },
            ]}
            value={filters.billing_cycle}
            onChange={(e) => setFilters({ ...filters, billing_cycle: e.target.value })}
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

      {/* Subscriptions Grid */}
      {loading ? (
        <div className="flex justify-center items-center py-12">
          <Spinner size="lg" />
        </div>
      ) : (
        <>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {subscriptions.length === 0 ? (
              <div className="col-span-3 text-center py-12 text-gray-500">
                No subscriptions found
              </div>
            ) : (
              subscriptions.map((sub) => (
                <Card key={sub.id} className="hover:shadow-md transition-shadow">
                  <div className="flex justify-between items-start">
                    <div>
                      <h3 className="font-semibold text-lg">{sub.name}</h3>
                      <p className="text-sm text-gray-500">{sub.customer?.name}</p>
                    </div>
                    <span className={`px-2 py-1 rounded-full text-xs font-medium ${getStatusColor(sub.status)}`}>
                      {getStatusLabel(sub.status)}
                    </span>
                  </div>
                  
                  <div className="mt-3 space-y-1">
                    <p className="text-2xl font-bold">
                      {formatCurrency(sub.amount, sub.currency)}
                      <span className="text-sm font-normal text-gray-500 ml-1">
                        / {getBillingCycleLabel(sub.billing_cycle)}
                      </span>
                    </p>
                    <p className="text-sm text-gray-500">
                      Next billing: {formatDate(sub.next_billing_date)}
                    </p>
                    {sub.end_date && (
                      <p className="text-sm text-gray-500">
                        Ends: {formatDate(sub.end_date)}
                      </p>
                    )}
                  </div>

                  <div className="mt-4 flex gap-2">
                    {sub.status === 'active' && (
                      <>
                        <Button size="sm" variant="outline" onClick={() => handlePause(sub.id)}>
                          Pause
                        </Button>
                        <Button size="sm" variant="danger" onClick={() => handleCancel(sub.id)}>
                          Cancel
                        </Button>
                      </>
                    )}
                    {sub.status === 'paused' && (
                      <Button size="sm" variant="success" onClick={() => handleResume(sub.id)}>
                        Resume
                      </Button>
                    )}
                    <Button size="sm" variant="outline" onClick={() => navigate(`/subscriptions/${sub.id}`)}>
                      View
                    </Button>
                  </div>
                </Card>
              ))
            )}
          </div>

          {pagination && (
            <div className="flex justify-between items-center mt-6">
              <p className="text-sm text-gray-500">
                Showing {pagination.from || 0} to {pagination.to || 0} of {pagination.total || 0}
              </p>
              <div className="flex gap-2">
                <Button
                  variant="outline"
                  size="sm"
                  disabled={!pagination.prev_page_url}
                  onClick={() => fetchSubscriptions({ ...filters, page: pagination.current_page - 1 })}
                >
                  Previous
                </Button>
                <Button
                  variant="outline"
                  size="sm"
                  disabled={!pagination.next_page_url}
                  onClick={() => fetchSubscriptions({ ...filters, page: pagination.current_page + 1 })}
                >
                  Next
                </Button>
              </div>
            </div>
          )}
        </>
      )}
    </div>
  );
};

export default SubscriptionIndex;