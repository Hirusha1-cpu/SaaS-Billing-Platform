import React, { useState } from 'react';
import { Button } from '../Common/Button';
import { Input } from '../Common/Input';
import { SparklesIcon } from '@heroicons/react/24/outline';
import toast from 'react-hot-toast';

export const AIPromptInput = ({ onGenerate, loading }) => {
  const [prompt, setPrompt] = useState('');

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!prompt.trim()) {
      toast.error('Please describe what you need');
      return;
    }
    await onGenerate(prompt);
  };

  return (
    <form onSubmit={handleSubmit} className="bg-gradient-to-r from-blue-50 to-purple-50 rounded-xl p-6">
      <div className="flex items-center gap-2 mb-3">
        <SparklesIcon className="w-5 h-5 text-blue-600" />
        <h3 className="font-semibold text-gray-800">AI Invoice Generator</h3>
      </div>
      <p className="text-sm text-gray-600 mb-3">
        Describe what you need in natural language. Example: "John Doeට Laptops 5ක්, එකක් රු.100,000, Dec 10 වෙනිදට සල්ලි දෙන්න ඕන"
      </p>
      <div className="flex gap-2">
        <Input
          placeholder="Type your request in Sinhala or English..."
          value={prompt}
          onChange={(e) => setPrompt(e.target.value)}
          className="flex-1"
        />
        <Button type="submit" disabled={loading}>
          {loading ? 'Generating...' : 'Generate'}
        </Button>
      </div>
    </form>
  );
};