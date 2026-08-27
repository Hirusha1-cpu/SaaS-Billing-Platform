import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { Button } from '../../Components/Common/Button';
import { Input } from '../../Components/Common/Input';
import api from '../../Utils/api';
import toast from 'react-hot-toast';

const ForgotPassword = () => {
  const [loading, setLoading] = useState(false);
  const [email, setEmail] = useState('');
  const [submitted, setSubmitted] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);

    try {
      await api.post('/auth/forgot-password', { email });
      setSubmitted(true);
      toast.success('Password reset link sent to your email!');
    } catch (error) {
      toast.error(error.response?.data?.error || 'Failed to send reset link');
    } finally {
      setLoading(false);
    }
  };

  if (submitted) {
    return (
      <div className="text-center">
        <div className="mb-4 p-4 bg-green-50 text-green-700 rounded-lg">
          <p className="font-semibold">Check your email!</p>
          <p className="text-sm mt-1">
            We've sent a password reset link to <strong>{email}</strong>
          </p>
        </div>
        <Link to="/login" className="text-blue-600 hover:underline">
          Back to Login
        </Link>
      </div>
    );
  }

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      <p className="text-sm text-gray-600 mb-4">
        Enter your email address and we'll send you a link to reset your password.
      </p>
      <Input
        label="Email Address"
        type="email"
        placeholder="you@example.com"
        value={email}
        onChange={(e) => setEmail(e.target.value)}
        required
      />
      <Button type="submit" className="w-full" disabled={loading}>
        {loading ? 'Sending...' : 'Send Reset Link'}
      </Button>
      <p className="text-center text-sm text-gray-600">
        Remember your password?{' '}
        <Link to="/login" className="text-blue-600 hover:underline">
          Login
        </Link>
      </p>
    </form>
  );
};

export default ForgotPassword;