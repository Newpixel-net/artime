import boto3
import os
from botocore.config import Config

# Load credentials from environment variables
BUCKET_NAME = os.environ.get("R2_BUCKET_NAME", "multitalk-videos")
ACCOUNT_ID = os.environ.get("R2_ACCOUNT_ID", "")
ACCESS_KEY_ID = os.environ.get("R2_ACCESS_KEY_ID", "")
ACCESS_KEY_SECRET = os.environ.get("R2_ACCESS_KEY_SECRET", "")

def create_presigned_url(
    bucket_name: str = BUCKET_NAME,
    object_name: str = None,
    operation: str = 'get_object',
    expiration: int = 3600,
    account_id: str = ACCOUNT_ID,
    access_key_id: str = ACCESS_KEY_ID,
    access_key_secret: str = ACCESS_KEY_SECRET,
    content_type: str = None
) -> str:
    """
    Generate a presigned URL for Cloudflare R2.
    
    Args:
        bucket_name (str): Name of the R2 bucket
        object_name (str): Name of the object/file in the bucket
        operation (str): S3 operation ('get_object' or 'put_object')
        expiration (int): Time in seconds until the presigned URL expires (default: 1 hour)
        account_id (str): Your Cloudflare account ID
        access_key_id (str): Your R2 access key ID
        access_key_secret (str): Your R2 access key secret
        content_type (str): Content type of the file (for uploads)
    
    Returns:
        str: Presigned URL for the object
    """
    
    # Configure the S3 client for R2
    s3_client = boto3.client(
        's3',
        endpoint_url=f'https://{account_id}.r2.cloudflarestorage.com',
        aws_access_key_id=access_key_id,
        aws_secret_access_key=access_key_secret,
        config=Config(
            signature_version='s3v4',
            region_name='auto'  # R2 uses 'auto' as the region
        )
    )
    
    try:
        # Set up the parameters for the URL generation
        params = {
            'Bucket': bucket_name,
            'Key': object_name
        }
        
        # Add content type for uploads if specified
        if operation == 'put_object' and content_type:
            params['ContentType'] = content_type
        
        # Generate the presigned URL
        response = s3_client.generate_presigned_url(
            operation,
            Params=params,
            ExpiresIn=expiration
        )
        return response
    except Exception as e:
        print(f"Error generating presigned URL: {e}")
        return None