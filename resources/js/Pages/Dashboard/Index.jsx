import React, { useState, useEffect } from 'react';
import { Card } from '../../Components/Common/Card';
import { Spinner } from '../../Components/Common/Spinner';
import { Badge } from '../../Components/Common/Badge';
import { Button } from '../../Components/Common/Button';
import api from '../../Utils/api';
import { formatCurrency, formatDate } from '../../Utils/helpers';
import { useAuth } from '../../Hooks/useAuth';
import { useNavigate } from 'react-router-dom';
import { 
  SparklesIcon, 
  DocumentTextIcon, 
  CurrencyDollarIcon,
  ClockIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  ArrowPathIcon
} from '@heroicons/react/24/outline';
import toast from 'react-hot-toast';

const Dashboard = () => {
  const navigate = useNavigate();
  const { user } = useAuth();
  const [stats, setStats] = useState(null);
  const [recentInvoices, setRecentInvoices] = useState([]);
  const [recentActivity, setRecentActivity] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchDashboardData();
  }, []);

  const fetchDashboardData = async () => {
    setLoading(true);
    try {
      // Fetch all data in parallel
      const [statsRes, invoicesRes, activityRes] = await Promise.all([
        api.get('/dashboard/stats'),
        api.get('/dashboard/recent-invoices?limit=5'),
        api.get('/dashboard/activity?limit=10'),
      ]);

      setStats(statsRes.data);
      setRecentInvoices(invoicesRes.data);
      setRecentActivity(activityRes.data);
    } catch (error) {
      console.error('Failed to fetch dashboard data:', error);
      toast.error('Failed to load dashboard data');
    } finally {
      setLoading(false);
    }
  };

  const getStatusColor = (status) => {
    const colors = {
      draft: 'gray',
      sent: 'blue',
      partially_paid: 'yellow',
      paid: 'green',
      overdue: 'red',
      cancelled: 'red',
      refunded: 'purple',
    };
    return colors[status] || 'gray';
  };

  const getStatusLabel = (status) => {
    const labels = {
      draft: 'Draft',
      sent: 'Sent',
      partially_paid: 'Partially Paid',
      paid: 'Paid',
      overdue: 'Overdue',
      cancelled: 'Cancelled',
      refunded: 'Refunded',
    };
    return labels[status] || status;
  };

  const getActivityIcon = (type) => {
    if (type === 'invoice') {
      return <DocumentTextIcon className="w-4 h-4 text-blue-500" />;
    } else if (type === 'payment') {
      return <CurrencyDollarIcon className="w-4 h-4 text-green-500" />;
    }
    return <ClockIcon className="w-4 h-4 text-gray-400" />;
  };

  if (loading) {
    return (
      <div className="flex justify-center items-center h-64">
        <Spinner size="lg" />
      </div>
    );
  }

  const statCards = [
    { 
      title: 'Total Invoices', 
      value: stats?.total_invoices || 0, 
      color: 'blue',
      icon: DocumentTextIcon,
    },
    { 
      title: 'Total Revenue', 
      value: formatCurrency(stats?.total_revenue || 0), 
      color: 'green',
      icon: CurrencyDollarIcon,
    },
    { 
      title: 'Pending Invoices', 
      value: stats?.pending_invoices || 0, 
      color: 'yellow',
      icon: ClockIcon,
    },
    { 
      title: 'Overdue Invoices', 
      value: stats?.overdue_invoices || 0, 
      color: 'red',
      icon: ExclamationTriangleIcon,
    },
  ];

  return (
    <div>
      <div className="flex justify-between items-center mb-6">
        <div>
          <h1 className="text-2xl font-bold">Dashboard</h1>
          <p className="text-sm text-gray-500 mt-1">
            Welcome back, {user?.name}! Here's your business overview.
          </p>
        </div>
        <div className="flex gap-2">
          <Button onClick={() => navigate('/invoices/create')} variant="primary">
            + New Invoice
          </Button>
          <Button onClick={() => navigate('/insights')} variant="outline">
            <SparklesIcon className="w-4 h-4 mr-2" />
            AI Insights
          </Button>
        </div>
      </div>
      
      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {statCards.map((stat, idx) => {
          const Icon = stat.icon;
          const colorClasses = {
            blue: 'bg-blue-50 text-blue-600 border-blue-200',
            green: 'bg-green-50 text-green-600 border-green-200',
            yellow: 'bg-yellow-50 text-yellow-600 border-yellow-200',
            red: 'bg-red-50 text-red-600 border-red-200',
          };
          return (
            <div key={idx} className="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition-shadow">
              <div className="flex items-start justify-between">
                <div>
                  <p className="text-sm text-gray-500">{stat.title}</p>
                  <p className="text-2xl font-bold text-gray-900 mt-1">{stat.value}</p>
                </div>
                <div className={`p-2 rounded-lg ${colorClasses[stat.color]}`}>
                  <Icon className="w-5 h-5" />
                </div>
              </div>
            </div>
          );
        })}
      </div>

      {/* Recent Invoices and Activity Log */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Recent Invoices */}
        <Card 
          title="Recent Invoices" 
          actions={
            <Button variant="outline" size="sm" onClick={() => navigate('/invoices')}>
              View All
            </Button>
          }
        >
          {recentInvoices.length === 0 ? (
            <div className="text-center py-8 text-gray-500">
              <DocumentTextIcon className="w-12 h-12 mx-auto text-gray-300 mb-2" />
              <p>No invoices yet</p>
              <Button variant="primary" size="sm" className="mt-2" onClick={() => navigate('/invoices/create')}>
                Create your first invoice
              </Button>
            </div>
          ) : (
            <div className="space-y-3">
              {recentInvoices.map((invoice) => (
                <div 
                  key={invoice.id} 
                  className="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer transition-colors"
                  onClick={() => navigate(`/invoices/${invoice.id}`)}
                >
                  <div className="flex-1">
                    <div className="flex items-center gap-2">
                      <span className="font-medium text-gray-900">#{invoice.invoice_number}</span>
                      <Badge variant={getStatusColor(invoice.status)}>
                        {getStatusLabel(invoice.status)}
                      </Badge>
                    </div>
                    <p className="text-sm text-gray-500 mt-0.5">
                      {invoice.customer?.name || 'Unknown Customer'} • {formatDate(invoice.created_at)}
                    </p>
                  </div>
                  <div className="text-right">
                    <p className="font-semibold text-gray-900">{formatCurrency(invoice.total, invoice.currency)}</p>
                    {invoice.balance_due > 0 && invoice.status !== 'paid' && (
                      <p className="text-xs text-red-500">Due: {formatCurrency(invoice.balance_due, invoice.currency)}</p>
                    )}
                  </div>
                </div>
              ))}
            </div>
          )}
        </Card>

        {/* Activity Log */}
        <Card 
          title="Recent Activity" 
          actions={
            <Button variant="outline" size="sm" onClick={fetchDashboardData}>
              <ArrowPathIcon className="w-4 h-4 mr-1" />
              Refresh
            </Button>
          }
        >
          {recentActivity.length === 0 ? (
            <div className="text-center py-8 text-gray-500">
              <ClockIcon className="w-12 h-12 mx-auto text-gray-300 mb-2" />
              <p>No recent activity</p>
            </div>
          ) : (
            <div className="space-y-3 max-h-96 overflow-y-auto pr-2">
              {recentActivity.map((activity, index) => (
                <div key={index} className="flex items-start gap-3 p-2 hover:bg-gray-50 rounded-lg transition-colors">
                  <div className="mt-0.5">
                    {getActivityIcon(activity.type)}
                  </div>
                  <div className="flex-1 min-w-0">
                    <p className="text-sm text-gray-800">{activity.description}</p>
                    <p className="text-xs text-gray-400 mt-0.5">
                      {formatDate(activity.created_at)}
                      {activity.amount && (
                        <span className="ml-2 font-medium text-green-600">
                          {formatCurrency(activity.amount)}
                        </span>
                      )}
                    </p>
                  </div>
                  {activity.type === 'invoice' && activity.status && (
                    <Badge variant={getStatusColor(activity.status)} size="sm">
                      {getStatusLabel(activity.status)}
                    </Badge>
                  )}
                </div>
              ))}
            </div>
          )}
        </Card>
      </div>
    </div>
  );
};

export default Dashboard;