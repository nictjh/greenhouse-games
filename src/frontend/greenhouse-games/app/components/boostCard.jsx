"use client";
import { Box, Flex, Text, Button, Image } from "@chakra-ui/react";

const BoostCard = () => {
  return (
    <Box
      bg="#708463"
      borderRadius="lg"
      p={6}
      mt={8}
      color="white"
      maxW="80%"
      overflow="hidden"
    >
      <Flex
        direction={{ base: "column", md: "row" }}
        align="center"
        justify="space-between"
        gap={6}
      >
        {/* Left Side - Text */}
        <Box flex="1">
          <Text fontSize="xs" fontWeight="bold" letterSpacing="wide" mb={2}>
            BOOST ENGAGEMENT
          </Text>
          <Text fontSize="2xl" fontWeight="bold" mb={4} lineHeight="short">
            FIND NEW WAYS <br />
            TO IMPROVE <br />
            LEARNING OUTCOMES
          </Text>
          <Button
            colorScheme="gray"
            bg="#4C5B78"
            borderRadius="md"
            px={6}
            _hover={{ bg: "#3B4A66" }}
          >
            Explore Latest
          </Button>
        </Box>

        {/* Right Side - Image Box */}
        <Box
          bg="yellow.300"
          flexShrink={0}
          borderRadius="xl"
          minW="200px"
          minH="200px"
          display="flex"
          alignItems="center"
          justifyContent="center"
        >
          <Text color="black" fontWeight="bold">
            IMAGE
          </Text>
        </Box>
      </Flex>
    </Box>
  );
};

export default BoostCard;
