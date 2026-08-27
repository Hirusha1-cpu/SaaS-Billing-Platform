import React, { useState, useEffect } from 'react';
import { Card } from '../../Components/Common/Card';
import { Spinner } from '../../Components/Common/Spinner';
import api from '../../Utils/api';
import { formatCurrency } from '../../Utils/helpers';
import { useAuth } from '../../Hooks/useAuth';
import { Button } from '../../Components/Common/Button';
import { useNavigate } from 'react-router-dom';
import { SparklesIcon } from '@heroicons/react/24/outline';

const Dashboard = () => {
  const navigate = useNavigate();
  const { user } = useAuth();
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchStats();
  }, []);

  const fetchStats = async () => {
    try {
      const response = await api.get('/dashboard/stats');
      setStats(response.data);
    } catch (error) {
      console.error('Failed to fetch stats:', error);
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="flex justify-center items-center h-64">
        <Spinner size="lg" />
      </div>
    );
  }

  const statCards = [
    { title: 'Total Invoices', value: stats?.total_invoices || 0, color: 'blue' },
    { title: 'Total Revenue', value: formatCurrency(stats?.total_revenue || 0), color: 'green' },
    { title: 'Pending Invoices', value: stats?.pending_invoices || 0, color: 'yellow' },
    { title: 'Overdue Invoices', value: stats?.overdue_invoices || 0, color: 'red' },
  ];

  return (
    <div>
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-2xl font-bold">Dashboard</h1>
        <Button onClick={() => navigate('/insights')} variant="outline">
          <SparklesIcon className="w-4 h-4 mr-2" />
          AI Insights
        </Button>
      </div>
      
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {statCards.map((stat, idx) => (
          <Card key={idx} className="text-center">
            <p className="text-sm text-gray-500">{stat.title}</p>
            <p className="text-2xl font-bold text-gray-900">{stat.value}</p>
          </Card>
        ))}
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <Card title="Recent Invoices">
          <p className="text-gray-500">Coming soon...</p>
        </Card>
        <Card title="Activity Log">
          <p className="text-gray-500">Coming soon...</p>
        </Card>
      </div>
    </div>
  );
};

export default Dashboard;