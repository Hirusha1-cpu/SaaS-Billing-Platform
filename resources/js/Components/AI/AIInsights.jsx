import React, { useState, useEffect } from 'react';
import { Card } from '../Common/Card';
import { Button } from '../Common/Button';
import { Spinner } from '../Common/Spinner';
import { Input } from '../Common/Input';
import api from '../../Utils/api';
import { formatCurrency } from '../../Utils/helpers';
import { 
  SparklesIcon, 
  CurrencyDollarIcon,
  DocumentTextIcon,
  UserGroupIcon,
  ExclamationTriangleIcon,
  CheckCircleIcon,
} from '@heroicons/react/24/outline';
import toast from 'react-hot-toast';

const AIInsights = () => {
  const [loading, setLoading] = useState(false);
  const [insights, setInsights] = useState(null);
  const [stats, setStats] = useState(null);
  const [prompt, setPrompt] = useState('');
  const [insightType, setInsightType] = useState('overview');
  const [generating, setGenerating] = useState(false);

  const insightTypes = [
    { value: 'overview', label: '📊 Overview' },
    { value: 'revenue', label: '💰 Revenue Analysis' },
    { value: 'customers', label: '👥 Customer Insights' },
    { value: 'invoices', label: '📄 Invoice Trends' },
    { value: 'overdue', label: '⚠️ Overdue Analysis' },
    { value: 'subscriptions', label: '🔄 Subscription Insights' },
  ];

  useEffect(() => {
    fetchStats();
    generateInsights('overview');
  }, []);

  const fetchStats = async () => {
    try {
      const response = await api.get('/dashboard/stats');
      setStats(response.data);
    } catch (error) {
      console.error('Failed to fetch stats:', error);
    }
  };

  const generateInsights = async (type) => {
    setLoading(true);
    setInsightType(type);
    
    try {
      const response = await api.post('/ai/insights', {
        type: type,
        stats: stats,
      });
      setInsights(response.data);
      
      if (response.data.insights) {
        toast.success('Insights generated successfully!');
      }
    } catch (error) {
      toast.error('Failed to generate insights');
      console.error('AI Insights error:', error);
      // Set fallback insights if API fails
      setInsights({
        insights: 'AI insights are currently unavailable. Please try again later.',
        recommendations: ['Check your internet connection', 'Try again in a few minutes']
      });
    } finally {
      setLoading(false);
    }
  };

  const handleCustomPrompt = async () => {
    if (!prompt.trim()) {
      toast.error('Please enter your question');
      return;
    }

    setGenerating(true);
    try {
      const response = await api.post('/ai/insights', {
        prompt: prompt,
        stats: stats,
        type: 'custom',
      });
      setInsights(response.data);
      toast.success('AI response generated!');
      setPrompt('');
    } catch (error) {
      toast.error('Failed to generate response');
      console.error('AI custom prompt error:', error);
      // Set fallback
      setInsights({
        insights: 'I\'m having trouble processing your request. Please try again.',
        recommendations: ['Try asking a different question', 'Check your connection']
      });
    } finally {
      setGenerating(false);
    }
  };

  const StatCard = ({ title, value, icon: Icon, color = 'blue', subtitle }) => {
    const colors = {
      blue: 'bg-blue-50 text-blue-600',
      green: 'bg-green-50 text-green-600',
      red: 'bg-red-50 text-red-600',
      yellow: 'bg-yellow-50 text-yellow-600',
      purple: 'bg-purple-50 text-purple-600',
    };

    return (
      <div className="bg-white rounded-xl shadow-sm p-4 border border-gray-100">
        <div className="flex items-start justify-between">
          <div>
            <p className="text-sm text-gray-500">{title}</p>
            <p className="text-2xl font-bold text-gray-900 mt-1">{value}</p>
            {subtitle && <p className="text-xs text-gray-400 mt-1">{subtitle}</p>}
          </div>
          <div className={`p-2 rounded-lg ${colors[color]}`}>
            <Icon className="w-5 h-5" />
          </div>
        </div>
      </div>
    );
  };

  // Loading state for stats
  if (!stats) {
    return (
      <div className="flex justify-center items-center h-64">
        <Spinner size="lg" />
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h2 className="text-2xl font-bold text-gray-900">AI Insights</h2>
          <p className="text-sm text-gray-500 mt-1">
            Get intelligent insights and analysis of your business data
          </p>
        </div>
        <div className="flex items-center gap-2">
          <SparklesIcon className="w-5 h-5 text-purple-600" />
          <span className="text-sm font-medium text-purple-600">Powered by Gemini AI</span>
        </div>
      </div>

      {/* Stats Overview */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard
          title="Total Revenue"
          value={formatCurrency(stats?.total_revenue || 0)}
          icon={CurrencyDollarIcon}
          color="green"
          subtitle={`${stats?.paid_invoices || 0} paid invoices`}
        />
        <StatCard
          title="Total Invoices"
          value={stats?.total_invoices || 0}
          icon={DocumentTextIcon}
          color="blue"
          subtitle={`${stats?.draft_invoices || 0} draft`}
        />
        <StatCard
          title="Customers"
          value={stats?.total_customers || 0}
          icon={UserGroupIcon}
          color="purple"
          subtitle={`${stats?.active_customers || 0} active`}
        />
        <StatCard
          title="Overdue"
          value={stats?.overdue_invoices || 0}
          icon={ExclamationTriangleIcon}
          color="red"
          subtitle={`${formatCurrency(stats?.pending_amount || 0)} pending`}
        />
      </div>

      {/* Insight Type Selector */}
      <div className="flex flex-wrap gap-2">
        {insightTypes.map((type) => (
          <Button
            key={type.value}
            variant={insightType === type.value ? 'primary' : 'outline'}
            size="sm"
            onClick={() => generateInsights(type.value)}
            disabled={loading}
          >
            {type.label}
          </Button>
        ))}
      </div>

      {/* Loading State */}
      {loading && (
        <div className="flex justify-center items-center py-12">
          <Spinner size="lg" />
          <p className="ml-3 text-gray-500">Generating insights...</p>
        </div>
      )}

      {/* Insights Display */}
      {!loading && insights && (
        <Card title={insights.title || 'AI Insights'} className="mt-4">
          <div className="prose max-w-none">
            {typeof insights.insights === 'string' ? (
              <div className="whitespace-pre-wrap text-gray-700 leading-relaxed">
                {insights.insights}
              </div>
            ) : (
              <div className="space-y-4">
                {insights.insights?.map((item, index) => (
                  <div key={index} className="p-4 bg-gray-50 rounded-lg">
                    <p className="text-gray-700">{item}</p>
                  </div>
                ))}
              </div>
            )}

            {insights.recommendations && (
              <div className="mt-6 pt-4 border-t border-gray-200">
                <h4 className="font-semibold text-gray-900 mb-2">📌 Recommendations</h4>
                <ul className="list-disc list-inside space-y-1 text-gray-700">
                  {insights.recommendations.map((rec, idx) => (
                    <li key={idx}>{rec}</li>
                  ))}
                </ul>
              </div>
            )}
          </div>
        </Card>
      )}

      {/* Custom Prompt */}
      <Card title="Ask AI Anything">
        <div className="flex gap-2">
          <Input
            placeholder="Ask a question about your business..."
            value={prompt}
            onChange={(e) => setPrompt(e.target.value)}
            className="flex-1"
            onKeyDown={(e) => e.key === 'Enter' && handleCustomPrompt()}
          />
          <Button onClick={handleCustomPrompt} disabled={generating}>
            {generating ? (
              <>
                <Spinner size="sm" className="mr-2" />
                Thinking...
              </>
            ) : (
              <>
                <SparklesIcon className="w-4 h-4 mr-2" />
                Ask AI
              </>
            )}
          </Button>
        </div>
        <p className="text-xs text-gray-400 mt-2">
          Ask about revenue trends, customer behavior, invoice patterns, or any business question
        </p>
      </Card>

      {/* Quick Questions */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
        <button
          onClick={() => {
            setPrompt('What are my top 5 customers?');
            setTimeout(handleCustomPrompt, 100);
          }}
          className="text-left p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors text-sm text-gray-700"
        >
          🏆 Who are my top customers?
        </button>
        <button
          onClick={() => {
            setPrompt('What is my revenue trend for the last 3 months?');
            setTimeout(handleCustomPrompt, 100);
          }}
          className="text-left p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors text-sm text-gray-700"
        >
          📈 Revenue trend analysis?
        </button>
        <button
          onClick={() => {
            setPrompt('Which invoices are overdue and what should I do?');
            setTimeout(handleCustomPrompt, 100);
          }}
          className="text-left p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors text-sm text-gray-700"
        >
          ⚠️ Overdue invoice actions?
        </button>
      </div>
    </div>
  );
};

export default AIInsights;