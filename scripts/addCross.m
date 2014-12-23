function [ imgOut ] = addCross( img, cross )
%UNTITLED Summary of this function goes here
%   Detailed explanation goes here

img = rgb2gray(im2single(img));
imgOut = repmat(img,1,1,3);
cross = im2single(cross);

imgOut(:,:,2) = max(0,imgOut(:,:,2) - cross);
imgOut(:,:,3) = max(0,imgOut(:,:,3) - cross);

end

