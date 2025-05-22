#!/bin/bash

# CONFIGURATION
source .env.deploy

# Build and submit image to Container Registry
echo "🚧 Building and submitting Docker image to Google Cloud Build..."
gcloud builds submit --tag gcr.io/$PROJECT_ID/$IMAGE_NAME

# Deploy to Cloud Run
echo "☁️ Deploying to Google Cloud Run..."
gcloud run deploy $SERVICE_NAME \
  --image gcr.io/$PROJECT_ID/$IMAGE_NAME \
  --platform managed \
  --region $REGION \
  --allow-unauthenticated \
  --set-env-vars MONGODB_URI="$MONGODB_URI"

echo "✅ Deployment complete!"
